@extends('layouts.app')

@section('title', 'Checkout')

@push('styles')
  @vite('resources/css/pages/checkout.css')
@endpush

@push('scripts')
  @vite('resources/js/page-scripts/checkout-expiry.js')
  @vite('resources/js/page-scripts/checkout-payment.js')
@endpush
       
@section('content')
    @php
      $isCreatorFeeCheckout = !empty($creatorFeeCheckout);
      $creatorFeeAmount = (float) ($creatorFeeCheckout->pay_total_amount ?? 80);
      $creatorFeeCurrency = $creatorFeeCheckout->pay_currency ?? 'GBP';
      $creatorFeeSymbol = $currencySymbols[$creatorFeeCurrency] ?? null;
      $paymentReferenceId = $isCreatorFeeCheckout
        ? ('CF-' . ($creatorFeeCheckout->collection_id ?? ($creatorFeeCheckout->draft_id ?? 'NEW')))
        : ($order->id ?? 'PENDING');
      $walletNetworkValue = $isCreatorFeeCheckout
        ? $creatorFeeCurrency
        : ($order->pay_currency ?? 'GBP');
    @endphp

    {{-- Checkout --}}
    @if($order)
      <div
        id="checkoutExpiry"
        class="checkout-expiry-banner"
        data-expires-at="{{ optional($order->expires_at)->toIso8601String() }}"
      ></div>
    @endif

    <div class="checkoutContainer {{ $order ? 'has-expiry-banner' : '' }}">
      <h1>Checkout</h1>

      @if ($errors->any())
        <div class="checkout-errors" role="alert" aria-live="polite">
          <strong>Please correct the highlighted checkout details.</strong>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Success --}}
      @if(session('status'))
        <div style="background: #4CAF50; color: white; padding: 15px; margin: 20px auto; max-width: 800px; border-radius: 8px; text-align: center;">
            {{ session('status') }}
        </div>
      @endif

      {{-- Error --}}
      @if(session('error'))
        <div style="background: #f44336; color: white; padding: 15px; margin: 20px auto; max-width: 800px; border-radius: 8px; text-align: center;">
            {{ session('error') }}
        </div>
      @endif

      @php
        $subtotal = 0;
        $displayCurrency = null;
      @endphp

      {{-- Empty --}}
      @if(!$order && !$isCreatorFeeCheckout)
        <p style="text-align: center; padding: 40px;">Your cart is empty or checkout has expired. <a href="/products">Browse products</a></p>
      @else
        <form method="POST" action="{{ route('orders.store') }}">
          @csrf
          <input type="hidden" name="checkout_context" value="{{ $isCreatorFeeCheckout ? 'creator_fee' : 'cart' }}">

          {{-- Shipping --}}
          <section class="checkoutSection">
            <h2>Shipping Information</h2>
            <p class="checkout-help-text">Use real-looking details. Names, addresses, and payment fields are now validated more strictly.</p>
            <div style="display: flex; flex-direction: column; gap: 15px;">
              <input type="text" name="full_name" placeholder="Full Name" value="{{ old('full_name') }}" autocomplete="name" minlength="5" maxlength="120" pattern="^(?=.{5,120}$)[A-Za-z][A-Za-z'.-]+(?:\s+[A-Za-z][A-Za-z'.-]+)+$" title="Enter your first and last name using letters only." required />
              <input type="text" name="address" placeholder="Address" value="{{ old('address') }}" autocomplete="street-address" minlength="8" maxlength="255" pattern="^(?=.{8,255}$)(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9][A-Za-z0-9\s,.'/#-]+$" title="Enter a street address with both letters and numbers." required />
              <input type="text" name="city" placeholder="City" value="{{ old('city') }}" autocomplete="address-level2" minlength="2" maxlength="80" pattern="^(?=.{2,80}$)[A-Za-z][A-Za-z\s'.-]*$" title="City should only contain letters, spaces, hyphens, apostrophes, or periods." required />
              <input type="text" name="postal_code" placeholder="Postal Code" value="{{ old('postal_code') }}" autocomplete="postal-code" minlength="3" maxlength="12" pattern="^(?=.{3,12}$)[A-Za-z0-9][A-Za-z0-9\s-]*$" title="Use letters, numbers, spaces, or hyphens only." required />
            </div>
          </section>

          {{-- Summary --}}
          <section class="checkoutSection">
            <h2>{{ $isCreatorFeeCheckout ? 'Creator Fee' : 'Your Order' }}</h2>

            <div style="margin-bottom: 20px;">
              @if ($isCreatorFeeCheckout)
                @php
                  $subtotal = $creatorFeeAmount;
                  $displayCurrency = $creatorFeeCurrency;
                @endphp
                <div style="display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #ddd; margin-bottom: 10px;">
                  <div>
                    <strong>Collection Creation Fee</strong><br>
                    <small>
                      {{ $creatorFeeCheckout->collection_name ?? 'Pending collection' }}
                      @if (!empty($creatorFeeCheckout->nft_count))
                        | {{ (int) $creatorFeeCheckout->nft_count }} NFTs
                      @endif
                    </small>
                  </div>
                  <div>
                    <strong>{{ $creatorFeeSymbol ? $creatorFeeSymbol . number_format($creatorFeeAmount, 2) : number_format($creatorFeeAmount, 2) . ' ' . $creatorFeeCurrency }}</strong>
                  </div>
                </div>
              @else
                @foreach($order->items as $item)
                  @php
                    $listing = $item->listing;
                    $nft = $listing?->token?->nft;
                    $itemTotal = ($item->pay_unit_amount ?? 0) * $item->quantity;
                    $subtotal += $itemTotal;
                    $nftName = $nft?->name ?? 'NFT';
                    $currency = $item->pay_currency ?? ($order->pay_currency ?? 'GBP');
                    $displayCurrency = $displayCurrency ?: $currency;
                    $currencySymbol = $currencySymbols[$currency] ?? null;
                  @endphp

                  <div style="display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #ddd; margin-bottom: 10px;">
                    <div>
                      <strong>{{ $nftName }}</strong><br>
                      <small>Listing #{{ $listing?->id }} | Quantity: {{ $item->quantity }}</small>
                    </div>
                    <div>
                      <strong>{{ $currencySymbol ? $currencySymbol . number_format($itemTotal, 2) : number_format($itemTotal, 2) . ' ' . $currency }}</strong>
                    </div>
                  </div>
                @endforeach
              @endif

              <div style="display: flex; justify-content: space-between; padding: 15px 10px; border-top: 2px solid #333; margin-top: 15px;">
                <strong>Total:</strong>
                @php
                  $summaryCurrency = $displayCurrency ?? 'GBP';
                  $summarySymbol = $currencySymbols[$summaryCurrency] ?? null;
                @endphp
                <strong>{{ $summarySymbol ? $summarySymbol . number_format($subtotal, 2) : number_format($subtotal, 2) . ' ' . $summaryCurrency }}</strong>
              </div>
            </div>
          </section>

          <section class="checkoutSection">
            <h2>Payment Method</h2>
            <div class="payment-methods">
              <label class="payment-option">
                <input type="radio" name="provider" value="mock_bank" {{ old('provider') === 'mock_bank' ? 'checked' : '' }} required>
                <span class="payment-option__title">Bank Transfer</span>
                <span class="payment-option__desc">Simulate a bank transfer through the banking subsystem.</span>
              </label>
              <label class="payment-option">
                <input type="radio" name="provider" value="mock_crypto" {{ old('provider') === 'mock_crypto' ? 'checked' : '' }} required>
                <span class="payment-option__title">Crypto Wallet</span>
                <span class="payment-option__desc">Simulate a crypto payment using wallet details.</span>
              </label>
              <label class="payment-option">
                <input type="radio" name="provider" value="mock_wallet" {{ old('provider') === 'mock_wallet' ? 'checked' : '' }} required>
                <span class="payment-option__title">9Mint Wallet</span>
                <span class="payment-option__desc">Pay using your 9Mint wallet balance.</span>
              </label>
            </div>

            <div class="payment-details is-hidden" data-provider="mock_bank">
              <h3>Bank details</h3>
              <p class="payment-instructions">Enter the mock bank transfer details that should be recorded for this payment.</p>
              <div class="payment-fields">
                <input type="text" name="bank_account_name" placeholder="Account name (e.g. Alex Smith)" value="{{ old('bank_account_name') }}" autocomplete="name" minlength="3" maxlength="120" pattern="^(?=.{3,120}$)[A-Za-z0-9][A-Za-z0-9\s&amp;.'-]*$" title="Use a valid personal or business account name." required>
                <input type="text" name="bank_sort_code" placeholder="Sort code (e.g. 12-34-56)" value="{{ old('bank_sort_code') }}" inputmode="numeric" maxlength="8" pattern="^\d{2}-\d{2}-\d{2}$" title="Sort code must match 12-34-56." required>
                <input type="text" name="bank_account_number" placeholder="Account number (e.g. 12345678)" value="{{ old('bank_account_number') }}" inputmode="numeric" maxlength="8" pattern="^\d{8}$" title="Account number must be exactly 8 digits." required>
                <input type="text" name="bank_reference" placeholder="Payment reference (Order #{{ $paymentReferenceId }})" value="{{ old('bank_reference') }}" minlength="4" maxlength="40" pattern="^(?=.{4,40}$)[A-Za-z0-9][A-Za-z0-9\s/#-]*$" title="Reference can use letters, numbers, spaces, hyphens, slashes, or #." required>
              </div>
            </div>

            <div class="payment-details is-hidden" data-provider="mock_crypto">
              <h3>Wallet details</h3>
              <p class="payment-instructions">Enter the payer wallet details for this mock crypto payment.</p>
              <div class="payment-fields">
                <input type="text" name="wallet_address" placeholder="Wallet address (e.g. 0x...)" value="{{ old('wallet_address') }}" autocomplete="off" minlength="24" maxlength="128" pattern="^(0x[a-fA-F0-9]{40}|[A-Za-z0-9]{24,128})$" title="Use a valid wallet address." required>
                <input type="text" name="wallet_tag" placeholder="Memo / Tag" value="{{ old('wallet_tag') }}" autocomplete="off" minlength="3" maxlength="50" pattern="^(?=.{3,50}$)[A-Za-z0-9][A-Za-z0-9._-]*$" title="Memo / Tag can use letters, numbers, dots, hyphens, or underscores." required>
                <input type="text" name="wallet_network" value="{{ old('wallet_network', $walletNetworkValue) }}" readonly pattern="^[A-Z0-9_-]{2,20}$" title="Wallet network must be a valid code." required>
              </div>
            </div>

            <div class="payment-details is-hidden" data-provider="mock_wallet">
              @php $walletBalances = $walletBalances ?? collect(); @endphp
              <h3>9Mint wallet</h3>
              <p class="payment-instructions">Select which wallet currency to pay from.</p>
              <div class="payment-fields">
                <label>
                  Wallet currency
                  <select name="wallet_currency" data-wallet-currency-select required>
                    @foreach ($walletBalances as $balance)
                      <option value="{{ $balance->currency }}" data-balance="{{ $balance->balance ?? 0 }}" {{ old('wallet_currency') === $balance->currency ? 'selected' : '' }}>
                        {{ $balance->currency }}
                      </option>
                    @endforeach
                  </select>
                </label>
                <span class="payment-wallet-balance" data-wallet-balance>
                  Balance: --
                </span>
              </div>
            </div>

            <div
              class="payment-summary is-hidden"
              data-payment-summary
              data-pay-amount="{{ $isCreatorFeeCheckout ? $creatorFeeAmount : $order->pay_total_amount }}"
              data-pay-currency="{{ $isCreatorFeeCheckout ? $creatorFeeCurrency : $order->pay_currency }}"
              data-ref-amount="{{ $isCreatorFeeCheckout ? $creatorFeeAmount : $order->ref_total_amount }}"
              data-ref-currency="{{ $isCreatorFeeCheckout ? $creatorFeeCurrency : $order->ref_currency }}"
            >
              <p class="payment-summary__amount" data-payment-amount>
                @php
                  $summaryPayCurrency = $isCreatorFeeCheckout ? $creatorFeeCurrency : ($order->pay_currency ?? 'GBP');
                  $summaryPayAmount = $isCreatorFeeCheckout ? $creatorFeeAmount : ($order->pay_total_amount ?? 0);
                  $paySymbol = $currencySymbols[$summaryPayCurrency] ?? null;
                @endphp
                Amount due: {{ $paySymbol
                    ? $paySymbol . number_format($summaryPayAmount, 2)
                    : number_format($summaryPayAmount, 2) . ' ' . $summaryPayCurrency }}
              </p>
              <p class="payment-summary__hint" data-wallet-network-row>
                Network: <span data-wallet-network>ETH</span>
              </p>
              <p class="payment-summary__hint" data-conversion-text>
                @if ($isCreatorFeeCheckout)
                  Conversion: {{ $creatorFeeCurrency }} {{ number_format($creatorFeeAmount, 2) }}
                  equals {{ $creatorFeeCurrency }} {{ number_format($creatorFeeAmount, 2) }} at checkout time.
                @elseif ($order->ref_currency)
                  Conversion: {{ $order->ref_currency }} {{ number_format($order->ref_total_amount ?? 0, 2) }}
                  equals {{ $order->pay_currency }} {{ number_format($order->pay_total_amount ?? 0, 2) }} at checkout time.
                @else
                  Conversion locked at checkout time.
                @endif
              </p>
            </div>
          </section>

          <button type="submit" class="checkout-place-order">Place Order</button>
        </form>
      @endif
    </div>
@endsection
