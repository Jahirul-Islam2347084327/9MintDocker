<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\UnavailableCartItemsException;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Collection;
use App\Models\Nft;
use App\Models\Order;
use App\Models\Wallet;
use App\Services\CheckoutService;
use App\Services\PaymentOrchestratorService;
use App\Services\ThumbnailService;
use App\Services\Pricing\CurrencyCatalogInterface;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $checkoutService = app(CheckoutService::class);
        $creatorFeeCollection = null;
        $creatorFeeCheckout = null;
        $creatorFeeDraft = $request->session()->get('creator_fee_draft');
        $creatorFeeCollectionId = $request->session()->get('creator_fee_collection_id');

        if (is_array($creatorFeeDraft) && ! empty($creatorFeeDraft['id'])) {
            $creatorFeeCheckout = (object) [
                'draft_id' => $creatorFeeDraft['id'],
                'collection_id' => null,
                'collection_name' => $creatorFeeDraft['name'] ?? 'Pending collection',
                'nft_count' => count($creatorFeeDraft['nfts'] ?? []),
                'pay_currency' => 'GBP',
                'pay_total_amount' => (float) ($creatorFeeDraft['creation_fee_amount_gbp'] ?? 80.00),
                'ref_currency' => 'GBP',
                'ref_total_amount' => (float) ($creatorFeeDraft['creation_fee_amount_gbp'] ?? 80.00),
            ];
        } elseif ($creatorFeeCollectionId) {
            $creatorFeeCollection = Collection::query()
                ->where('id', $creatorFeeCollectionId)
                ->where('submitted_by_user_id', $user->id)
                ->first();

            if (! $creatorFeeCollection || $creatorFeeCollection->creation_fee_payment_state !== 'unpaid') {
                $request->session()->forget('creator_fee_collection_id');
                $creatorFeeCollection = null;
            } else {
                $creatorFeeCheckout = (object) [
                    'collection_id' => $creatorFeeCollection->id,
                    'draft_id' => null,
                    'collection_name' => $creatorFeeCollection->name,
                    'nft_count' => $creatorFeeCollection->nfts()->count(),
                    'pay_currency' => 'GBP',
                    'pay_total_amount' => 80.00,
                    'ref_currency' => 'GBP',
                    'ref_total_amount' => 80.00,
                ];
            }
        }

        $order = null;
        if (! $creatorFeeCheckout) {
            $removedCount = $checkoutService->removeUnavailableCartItems($user);
            if ($removedCount > 0) {
                $request->session()->forget('checkout_order_id');

                return redirect()->route('cart.index')
                    ->with('error', 'One or more items in your basket were no longer available and have been removed.');
            }

            $orderId = $request->session()->get('checkout_order_id');
            if ($orderId) {
                $order = Order::with('items.listing.token.nft')
                    ->where('id', $orderId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($order && $order->expires_at && $order->expires_at->isPast()) {
                    $request->session()->forget('checkout_order_id');
                    $order = null;
                }
            }

            if (! $order) {
                $items = CartItem::with('listing.token.nft')
                    ->where('user_id', $user->id)
                    ->get();

                if ($items->isNotEmpty()) {
                    $payCurrency = app(CurrencyCatalogInterface::class)->defaultPayCurrency();
                    try {
                        $order = $checkoutService->createOrderFromCart($user, $payCurrency);
                    } catch (UnavailableCartItemsException $e) {
                        CartItem::where('user_id', $user->id)
                            ->whereIn('listing_id', $e->listingIds())
                            ->delete();

                        $request->session()->forget('checkout_order_id');

                        return redirect()->route('cart.index')
                            ->with('error', 'One or more items in your basket were no longer available and have been removed.');
                    }

                    $request->session()->put('checkout_order_id', $order->id);
                }
            }
        }

        $currencyCatalog = app(CurrencyCatalogInterface::class);
        $enabledCurrencies = $currencyCatalog->listEnabledCurrencies();
        $balances = Schema::hasTable('wallets')
            ? Wallet::where('user_id', $user->id)->get()->keyBy('currency')
            : collect();
        $walletBalances = collect($enabledCurrencies)->map(function ($currency) use ($balances) {
            $balance = $balances[$currency]->balance ?? 0;
            return (object) ['currency' => $currency, 'balance' => (float) $balance];
        });

        return view('checkout', [
            'order' => $order?->load('items.listing.token.nft'),
            'walletBalances' => $walletBalances,
            'creatorFeeCollection' => $creatorFeeCollection,
            'creatorFeeCheckout' => $creatorFeeCheckout,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(
            $this->checkoutValidationRules(),
            $this->checkoutValidationMessages()
        );

        if ($request->input('checkout_context') === 'creator_fee') {
            return $this->storeCreatorFeeCheckout($request);
        }

        $orderId = $request->session()->get('checkout_order_id');
        $order = $orderId
            ? Order::where('id', $orderId)->where('user_id', $request->user()->id)->first()
            : null;

        if (! $order) {
            return redirect('/cart')->with('error', 'Checkout session expired. Please try again.');
        }

        if ($order->expires_at && $order->expires_at->isPast()) {
            $request->session()->forget('checkout_order_id');
            return redirect('/cart')->with('error', 'Checkout expired. Please try again.');
        }

        try {
            $intent = app(PaymentOrchestratorService::class)->processOrderPayment(
                $order->load('items.listing.token'),
                $request->user(),
                $request->input('provider'),
                $this->paymentPayload($request),
                'success'
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage() === 'Insufficient wallet balance'
                ? 'Insufficient wallet balance for this purchase.'
                : $e->getMessage());
        }

        if ($intent->status !== 'captured') {
            $request->session()->forget('checkout_order_id');
            return redirect('/cart')->with('error', 'Payment failed. Please try checkout again.');
        }

        $purchasedListingIds = $order->items->pluck('listing_id')->filter()->all();
        if (! empty($purchasedListingIds)) {
            CartItem::where('user_id', $request->user()->id)
                ->whereIn('listing_id', $purchasedListingIds)
                ->delete();
        }

        $request->session()->forget('checkout_order_id');

        return redirect('/cart')
            ->with('status', 'Order placed successfully! Order #' . $order->id);
    }

    private function storeCreatorFeeCheckout(Request $request)
    {
        $user = $request->user();
        $creatorFeeDraft = $request->session()->get('creator_fee_draft');
        $collectionId = $request->session()->get('creator_fee_collection_id');
        $collection = $collectionId
            ? Collection::query()
                ->where('id', $collectionId)
                ->where('submitted_by_user_id', $user->id)
                ->first()
            : null;

        $isDraftCheckout = is_array($creatorFeeDraft) && ! empty($creatorFeeDraft['id']);
        $isLegacyCollectionCheckout = $collection && $collection->creation_fee_payment_state === 'unpaid';

        if (! $isDraftCheckout && ! $isLegacyCollectionCheckout) {
            $request->session()->forget('creator_fee_draft');
            $request->session()->forget('creator_fee_collection_id');
            return redirect()->route('creator.collections.create')
                ->with('error', 'Creator fee checkout session expired. Please submit again.');
        }

        $provider = $request->input('provider');
        $payAmount = $isDraftCheckout
            ? (float) ($creatorFeeDraft['creation_fee_amount_gbp'] ?? 80.00)
            : 80.00;
        $payCurrency = 'GBP';

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'pay_currency' => $payCurrency,
            'pay_total_amount' => $payAmount,
            'ref_currency' => $payCurrency,
            'ref_total_amount' => $payAmount,
            'fx_rate' => ['GBP' => 1],
            'fx_rated_at' => now(),
            'placed_at' => now(),
            'expires_at' => now()->addMinutes(10),
            'checkout_token' => (string) Str::uuid(),
        ]);

        $paymentContext = [
            'collection_id' => $collection?->id,
            'creator_fee_draft_id' => $creatorFeeDraft['id'] ?? null,
            'collection_name' => $creatorFeeDraft['name'] ?? $collection?->name,
            'nft_count' => $isDraftCheckout
                ? count($creatorFeeDraft['nfts'] ?? [])
                : ($collection ? $collection->nfts()->count() : null),
        ];

        try {
            $paymentResult = app(PaymentOrchestratorService::class)->processCreatorFeePayment(
                $order,
                $user,
                $provider,
                $this->paymentPayload($request),
                'success',
                $paymentContext
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage() === 'Insufficient wallet balance'
                ? 'Insufficient wallet balance for creator fee hold.'
                : $e->getMessage());
        }

        $intent = $paymentResult['intent'];
        if ($intent->status !== 'captured') {
            return back()->with('error', 'Creator fee payment failed.');
        }

        $updateData = [
            'creation_fee_payment_state' => $paymentResult['creation_fee_payment_state'] ?? 'paid_unheld',
            'creation_fee_refund_state' => 'none',
            'creation_fee_order_id' => $order->id,
            'creation_fee_provider' => $paymentResult['creation_fee_provider'] ?? $provider,
            'creation_fee_amount_gbp' => $payAmount,
            'creation_fee_hold_currency' => $paymentResult['creation_fee_hold_currency'] ?? null,
            'creation_fee_hold_amount' => $paymentResult['creation_fee_hold_amount'] ?? null,
            'creation_fee_hold_reference' => $paymentResult['creation_fee_hold_reference'] ?? null,
        ];

        $order->update(['status' => 'paid']);

        $updateData['creation_fee_payment_intent_id'] = $intent->id;

        if ($isDraftCheckout) {
            $createdCollection = $this->createCollectionFromPaidDraft($creatorFeeDraft, $user, $updateData);
            $this->cleanupCreatorDraftAssets($creatorFeeDraft);

            $request->session()->forget('creator_fee_draft');
            $request->session()->forget('creator_fee_collection_id');

            return redirect()->route('creator.collections.create')
                ->with('status', 'Creator fee paid. Collection "' . $createdCollection->name . '" is now awaiting admin review.');
        }

        $collection->update($updateData);
        $request->session()->forget('creator_fee_collection_id');

        return redirect()->route('creator.collections.create')
            ->with('status', 'Creator fee paid. Your collection is now awaiting admin review.');
    }

    private function createCollectionFromPaidDraft(array $draft, $user, array $feeData): Collection
    {
        return DB::transaction(function () use ($draft, $user, $feeData) {
            $collection = Collection::create([
                'slug' => $this->uniqueCollectionSlug((string) ($draft['name'] ?? 'collection')),
                'name' => $draft['name'] ?? 'Untitled collection',
                'description' => $draft['description'] ?? null,
                'cover_image_url' => null,
                'creator_name' => $user->name,
                'submitted_by_user_id' => $user->id,
                'approval_status' => Collection::APPROVAL_PENDING,
                'is_public' => false,
                'creation_fee_payment_state' => $feeData['creation_fee_payment_state'] ?? 'paid_unheld',
                'creation_fee_refund_state' => $feeData['creation_fee_refund_state'] ?? 'none',
                'creation_fee_order_id' => $feeData['creation_fee_order_id'] ?? null,
                'creation_fee_payment_intent_id' => $feeData['creation_fee_payment_intent_id'] ?? null,
                'creation_fee_provider' => $feeData['creation_fee_provider'] ?? null,
                'creation_fee_amount_gbp' => $feeData['creation_fee_amount_gbp'] ?? 80.00,
                'creation_fee_hold_currency' => $feeData['creation_fee_hold_currency'] ?? null,
                'creation_fee_hold_amount' => $feeData['creation_fee_hold_amount'] ?? null,
                'creation_fee_hold_reference' => $feeData['creation_fee_hold_reference'] ?? null,
            ]);

            $collectionFolder = $collection->uploadFolderName();

            if (! empty($draft['cover_image_temp_path'])) {
                $coverImageUrl = $this->moveDraftCoverToCollectionThumbs(
                    (string) $draft['cover_image_temp_path'],
                    $collectionFolder
                );
                $collection->update(['cover_image_url' => $coverImageUrl]);
            }

            $draftNfts = array_values($draft['nfts'] ?? []);
            foreach ($draftNfts as $index => $nftInput) {
                $imageUrl = $this->moveDraftAssetToCollectionFolder(
                    (string) ($nftInput['image_temp_path'] ?? ''),
                    $collectionFolder,
                    'nft-' . ($index + 1)
                );

                Nft::create([
                    'collection_id' => $collection->id,
                    'slug' => $this->uniqueNftSlug((string) ($nftInput['name'] ?? ('NFT ' . ($index + 1)))),
                    'name' => $nftInput['name'] ?? ('NFT ' . ($index + 1)),
                    'description' => $nftInput['description'] ?? null,
                    'image_url' => $imageUrl,
                    'thumbnail_url' => ThumbnailService::generate(
                        public_path(ltrim($imageUrl, '/')),
                        "images/nfts/{$collectionFolder}/thumbs",
                        'thumb'
                    ),
                    'editions_total' => (int) ($nftInput['editions_total'] ?? 1),
                    'editions_remaining' => (int) ($nftInput['editions_total'] ?? 1),
                    'primary_ref_amount' => (float) ($nftInput['ref_amount'] ?? 0.01),
                    'primary_ref_currency' => strtoupper((string) ($draft['ref_currency'] ?? 'GBP')),
                    'is_active' => false,
                    'submitted_by_user_id' => $user->id,
                    'approval_status' => Nft::APPROVAL_PENDING,
                ]);
            }

            return $collection;
        });
    }

    private function moveDraftAssetToCollectionFolder(string $tempPath, string $collectionFolder, string $namePrefix): string
    {
        if ($tempPath === '' || ! Storage::disk('local')->exists($tempPath)) {
            abort(422, 'Collection draft files are missing. Please submit the collection again.');
        }

        $sourcePath = Storage::disk('local')->path($tempPath);
        $targetDirectory = public_path("images/nfts/{$collectionFolder}");
        if (! File::exists($targetDirectory)) {
            File::makeDirectory($targetDirectory, 0755, true);
        }

        $extension = strtolower((string) pathinfo($tempPath, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'png';
        }
        $filename = $namePrefix . '-' . Str::uuid() . '.' . $extension;
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;

        if (! File::copy($sourcePath, $targetPath)) {
            abort(500, 'Failed to prepare collection assets for submission.');
        }

        return "/images/nfts/{$collectionFolder}/{$filename}";
    }

    private function moveDraftCoverToCollectionThumbs(string $tempPath, string $collectionFolder): string
    {
        if ($tempPath === '' || ! Storage::disk('local')->exists($tempPath)) {
            abort(422, 'Collection draft cover image is missing. Please submit the collection again.');
        }

        $sourcePath = Storage::disk('local')->path($tempPath);
        $coverImageUrl = ThumbnailService::generateCover(
            $sourcePath,
            "images/nfts/{$collectionFolder}/thumbs",
            'cover'
        );

        if (! $coverImageUrl) {
            abort(500, 'Failed to prepare collection cover for submission.');
        }

        return $coverImageUrl;
    }

    private function cleanupCreatorDraftAssets(array $draft): void
    {
        if (! empty($draft['id'])) {
            Storage::disk('local')->deleteDirectory('creator-drafts/' . $draft['id']);
        }
    }

    private function uniqueCollectionSlug(string $name): string
    {
        $base = Str::slug($name);
        $root = $base !== '' ? $base : 'collection';
        $slug = $root;
        $i = 1;

        while (Collection::where('slug', $slug)->exists()) {
            $slug = $root . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function uniqueNftSlug(string $name): string
    {
        $base = Str::slug($name);
        $root = $base !== '' ? $base : 'nft';
        $slug = $root;
        $i = 1;

        while (Nft::where('slug', $slug)->exists()) {
            $slug = $root . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function paymentPayload(Request $request): array
    {
        return [
            'bank_account_name' => $request->input('bank_account_name'),
            'bank_sort_code' => $request->input('bank_sort_code'),
            'bank_account_number' => $request->input('bank_account_number'),
            'bank_reference' => $request->input('bank_reference'),
            'wallet_address' => $request->input('wallet_address'),
            'wallet_tag' => $request->input('wallet_tag'),
            'wallet_network' => $request->input('wallet_network'),
            'wallet_currency' => $request->input('wallet_currency'),
        ];
    }

    private function checkoutValidationRules(): array
    {
        $enabledCurrencies = app(CurrencyCatalogInterface::class)->listEnabledCurrencies();

        return [
            'full_name' => [
                'required',
                'string',
                'min:5',
                'max:120',
                'regex:/^(?=.{5,120}$)[A-Za-z][A-Za-z\'.-]+(?:\s+[A-Za-z][A-Za-z\'.-]+)+$/',
            ],
            'address' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'regex:/^(?=.{8,255}$)(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9][A-Za-z0-9\s,.\'\/#-]+$/',
            ],
            'city' => [
                'required',
                'string',
                'min:2',
                'max:80',
                'regex:/^(?=.{2,80}$)[A-Za-z][A-Za-z\s\'.-]*$/',
            ],
            'postal_code' => [
                'required',
                'string',
                'min:3',
                'max:12',
                'regex:/^(?=.{3,12}$)[A-Za-z0-9][A-Za-z0-9\s-]*$/',
            ],
            'provider' => ['required', 'in:mock_bank,mock_crypto,mock_wallet'],
            'bank_account_name' => [
                'required_if:provider,mock_bank',
                'string',
                'min:3',
                'max:120',
                'regex:/^(?=.{3,120}$)[A-Za-z0-9][A-Za-z0-9\s&.\'-]*$/',
            ],
            'bank_sort_code' => [
                'required_if:provider,mock_bank',
                'string',
                'regex:/^\d{2}-\d{2}-\d{2}$/',
            ],
            'bank_account_number' => [
                'required_if:provider,mock_bank',
                'string',
                'regex:/^\d{8}$/',
            ],
            'bank_reference' => [
                'required_if:provider,mock_bank',
                'string',
                'min:4',
                'max:40',
                'regex:/^(?=.{4,40}$)[A-Za-z0-9][A-Za-z0-9\s\/#-]*$/',
            ],
            'wallet_address' => [
                'required_if:provider,mock_crypto',
                'string',
                'max:128',
                'regex:/^(0x[a-fA-F0-9]{40}|[A-Za-z0-9]{24,128})$/',
            ],
            'wallet_tag' => [
                'required_if:provider,mock_crypto',
                'string',
                'min:3',
                'max:50',
                'regex:/^(?=.{3,50}$)[A-Za-z0-9][A-Za-z0-9._-]*$/',
            ],
            'wallet_network' => [
                'required_if:provider,mock_crypto',
                'string',
                'max:20',
                'regex:/^[A-Z0-9_-]{2,20}$/',
            ],
            'wallet_currency' => [
                'required_if:provider,mock_wallet',
                'string',
                Rule::in($enabledCurrencies),
            ],
        ];
    }

    private function checkoutValidationMessages(): array
    {
        return [
            'full_name.regex' => 'Enter your real full name using at least first and last name.',
            'address.regex' => 'Enter a valid street address that includes both letters and numbers.',
            'city.regex' => 'City names should only contain letters, spaces, hyphens, apostrophes, or periods.',
            'postal_code.regex' => 'Enter a valid postal code using letters, numbers, spaces, or hyphens.',
            'bank_account_name.regex' => 'Enter a valid bank account name.',
            'bank_sort_code.regex' => 'Sort code must be in the format 12-34-56.',
            'bank_account_number.regex' => 'Account number must be exactly 8 digits.',
            'bank_reference.regex' => 'Payment reference can only use letters, numbers, spaces, hyphens, slashes, or #.',
            'wallet_address.regex' => 'Enter a valid wallet address.',
            'wallet_tag.regex' => 'Memo / Tag can only use letters, numbers, dots, hyphens, or underscores.',
            'wallet_network.regex' => 'Wallet network must be a valid currency or network code.',
        ];
    }
}
