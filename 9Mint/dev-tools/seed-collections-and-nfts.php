<?php

/**
 * Simple seeding script for local/dev use.
 *
 * Usage (from project root):
 * php tools/seed-collections-and-nfts.php
 *
 * It will create (or update) the following:
 * - collections:
 * - glossy-collection
 * - superhero-collection
 * - geotennis-collection
 * - characters-collection
 * - fish-collection
 * - faces-collection
 * - fruitbeasts-collection
 * - aifruits-collection
 * - hamster-collection
 * - nfts belonging to those collections, matching your hard-coded views.
 */

use App\Models\Collection;
use App\Models\Listing;
use App\Models\Nft;
use App\Models\NftToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Seeding collections and NFTs...\n";

DB::listen(function ($query) {
    $sql = $query->sql;
    foreach ($query->bindings as $binding) {
        $bindingValue = is_numeric($binding) ? $binding : "'".str_replace("'", "''", (string) $binding)."'";
        $sql = preg_replace('/\?/', $bindingValue, $sql, 1);
    }
    echo "[DB] {$sql}\n";
});

$nineMintUser = User::updateOrCreate(
    ['id' => 1],
    ['name' => '9Mint', 'email' => null, 'password' => null, 'role' => 'admin']
);

$vlasUser = User::updateOrCreate(
    ['id' => 2],
    [
        'name' => 'Vlas',
        'email' => null,
        'password' => null,
        'role' => 'user',
        'badges' => [[
            'key' => 'trusted_seller',
            'label' => 'Trusted Seller',
            'description' => 'Recognized for consistent verified NFT sales and marketplace reliability.',
        ]],
    ]
);

$specialUser = User::updateOrCreate(
    ['name' => 'u_special_'],
    [
        'email' => null,
        'password' => null,
        'role' => 'user',
    ]
);

$dariuszUser = User::updateOrCreate(
    ['name' => 'Dariusz'],
    [
        'email' => null,
        'password' => null,
        'role' => 'user',
    ]
);

$almartsUser = User::updateOrCreate(
    ['name' => 'almarts27'],
    [
        'email' => null,
        'password' => null,
        'role' => 'user',
    ]
);

try {
    $nineMintUser->assignRole('admin');
} catch (\Throwable $e) {
    // admin
}

$sellerUserId = $vlasUser->id;


// --- Collections ---
$collections = [
    'glossy-collection' => [
        'name'            => 'Glossy Collection',
        'description'     => 'Glossy animal NFTs created by Vlas.',
        'cover_image_url' => '/images/nfts/glossy/GlossyDuckNFT.png',
        'creator_name'    => 'Vlas',
    ],
    'superhero-collection' => [
        'name'            => 'Superhero Collection',
        'description'     => 'Iconic superhero NFTs.',
        'cover_image_url' => '/images/nfts/superhero/Superman.png',
        'creator_name'    => 'Vlas',
    ],
    'geotennis-collection' => [
        'name'            => 'Geo Tennis',
        'description'     => 'Playing tennis with squares',
        'cover_image_url' => '/images/nfts/geotennis/t1.png',
        'creator_name'    => 'Vlas',
    ],
    'characters-collection' => [
        'name'            => 'Characters',
        'description'     => 'movie stars',
        'cover_image_url' => '/images/nfts/characters/carl.png',
        'creator_name'    => 'Vlas',
    ],
    'fish-collection' => [
        'name'            => 'Fish Collection',
        'description'     => 'A collection of aquatic fish.',
        'cover_image_url' => '/images/nfts/fish/fish1.png',
        'creator_name'    => 'Vlas',
    ],
    'faces-collection' => [
        'name'            => 'Faces Collection',
        'description'     => 'A collection of distinct faces.',
        'cover_image_url' => '/images/nfts/faces/face1.png',
        'creator_name'    => 'Vlas',
    ],
    'fruitbeasts-collection' => [
        'name'                 => 'Fruit Beasts',
        'description'          => 'Whimsical fruit-powered creatures created by u_special_.',
        'cover_image_url'      => '/images/nfts/fruitbeasts/Berrysnout.jpg',
        'creator_name'         => 'u_special_',
        'submitted_by_user_id' => $specialUser->id,
    ],
    'aifruits-collection' => [
        'name'                 => 'AI Fruits',
        'description'          => 'Animated AI fruit characters created by Dariusz.',
        'cover_image_url'      => '/images/nfts/aifruits/ai-fruit-apple.gif',
        'creator_name'         => 'Dariusz',
        'submitted_by_user_id' => $dariuszUser->id,
    ],
    'hamster-collection' => [
        'name'                 => 'Hamster Collection',
        'description'          => 'One-of-one hamster NFTs created by almarts27.',
        'cover_image_url'      => '/images/nfts/hamster/gem-hamster.jpg',
        'creator_name'         => 'almarts27',
        'submitted_by_user_id' => $almartsUser->id,
    ],
];

foreach ($collections as $slug => $data) {
    $collection = Collection::updateOrCreate(
        ['slug' => $slug],
        $data
    );
}

$glossy = Collection::where('slug', 'glossy-collection')->first();
$superhero = Collection::where('slug', 'superhero-collection')->first();
$geotennis = Collection::where('slug', 'geotennis-collection')->first();
$characters = Collection::where('slug', 'characters-collection')->first();
$fish = Collection::where('slug', 'fish-collection')->first();
$faces = Collection::where('slug', 'faces-collection')->first();
$fruitbeasts = Collection::where('slug', 'fruitbeasts-collection')->first();
$aifruits = Collection::where('slug', 'aifruits-collection')->first();
$hamster = Collection::where('slug', 'hamster-collection')->first();

if (!$glossy || !$superhero || !$geotennis || !$characters || !$fish || !$faces || !$fruitbeasts || !$aifruits || !$hamster) {
    echo "Error: collections not found after seeding. Aborting NFT creation.\n";
    exit(1);
}

// Common defaults
$editionsTotal = 5;

$collectionPricing = [
    'glossy-collection' => ['ref_amount' => 75.00, 'ref_currency' => 'GBP'],
    'superhero-collection' => ['ref_amount' => 100.00, 'ref_currency' => 'USD'],
    'geotennis-collection' => ['ref_amount' => 90.00, 'ref_currency' => 'EUR'],
    'characters-collection' => ['ref_amount' => 0.03, 'ref_currency' => 'ETH'],
    'fish-collection' => ['ref_amount' => 0.002, 'ref_currency' => 'BTC'],
    'faces-collection' => ['ref_amount' => 120.00, 'ref_currency' => 'GBP'],
    'fruitbeasts-collection' => ['ref_amount' => 85.00, 'ref_currency' => 'USD'],
    'aifruits-collection' => ['ref_amount' => 95.00, 'ref_currency' => 'EUR'],
    'hamster-collection' => ['ref_amount' => 65.00, 'ref_currency' => 'GBP'],
];

function seedCollectionListings(
    Collection $collection,
    array $nfts,
    array $pricing,
    int $editionsTotal,
    int $sellerUserId,
    User $owner
): void {
    foreach ($nfts as $data) {
        $nft = Nft::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'collection_id' => $collection->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'image_url' => $data['image_url'],
                'primary_ref_amount' => $pricing['ref_amount'],
                'primary_ref_currency' => $pricing['ref_currency'],
                'submitted_by_user_id' => $owner->id,
                'editions_total' => $editionsTotal,
                'editions_remaining' => $editionsTotal,
                'is_active' => true,
            ]
        );

        NftToken::where('nft_id', $nft->id)
            ->where('serial_number', '>', $editionsTotal)
            ->delete();

        for ($i = 1; $i <= $editionsTotal; $i++) {
            $token = NftToken::updateOrCreate(
                [
                    'nft_id' => $nft->id,
                    'serial_number' => $i,
                ],
                [
                    'owner_user_id' => $owner->id,
                    'status' => 'listed',
                ]
            );

            Listing::updateOrCreate(
                ['token_id' => $token->id],
                [
                    'seller_user_id' => $sellerUserId,
                    'status' => 'active',
                    'ref_amount' => $pricing['ref_amount'],
                    'ref_currency' => $pricing['ref_currency'],
                ]
            );
        }
    }
}

// --- Glossy NFTs ---
$glossyNfts = [
    [
        'slug'        => 'glossy-duck',
        'name'        => 'Glossy Duck',
        'description' => 'A pixelated, 3D-rendered duck shimmering with a high-gloss mosaic finish.',
        'image_url'   => '/images/nfts/glossy/GlossyDuckNFT.png',
    ],
    [
        'slug'        => 'glossy-cat',
        'name'        => 'Glossy Cat',
        'description' => 'A fragmented, glassy feline calmly observing the digital realm.',
        'image_url'   => '/images/nfts/glossy/GlossyCatNFT.png',
    ],
    [
        'slug'        => 'glossy-donkey',
        'name'        => 'Glossy Donkey',
        'description' => 'A shiny, blocky reimagining of a beloved animated sidekick.',
        'image_url'   => '/images/nfts/glossy/GlossyDonkeyNFT.png',
    ],
    [
        'slug'        => 'glossy-giraffe',
        'name'        => 'Glossy Giraffe',
        'description' => 'Reaching new heights with a tiled, highly reflective geometric coat.',
        'image_url'   => '/images/nfts/glossy/GlossyGiraffeNFT.png',
    ],
    [
        'slug'        => 'glossy-lobster',
        'name'        => 'Glossy Lobster',
        'description' => 'A vibrant red crustacean constructed from glossy, translucent digital scales.',
        'image_url'   => '/images/nfts/glossy/GlossyLobsterNFT.png',
    ],
    [
        'slug'        => 'glossy-rooster',
        'name'        => 'Glossy Rooster',
        'description' => 'Strutting its pixelated plumage with an undeniable, eye-catching sheen.',
        'image_url'   => '/images/nfts/glossy/GlossyRoosterNFT.png',
    ],
    [
        'slug'        => 'glossy-squirrel',
        'name'        => 'Glossy Squirrel',
        'description' => 'A glassy-eyed woodland creature rendered in polished mosaic tiles.',
        'image_url'   => '/images/nfts/glossy/GlossySquirrelNFT.png',
    ],
];

seedCollectionListings(
    $glossy,
    $glossyNfts,
    $collectionPricing['glossy-collection'],
    25,
    $sellerUserId,
    $vlasUser
);

// --- Superhero NFTs ---
$superheroNfts = [
    [
        'slug'        => 'aquaman',
        'name'        => 'Aquaman',
        'description' => 'A uniquely drawn king of the sea featuring a rather questionable MS-Paint beard.',
        'image_url'   => '/images/nfts/superhero/Aquaman.png',
    ],
    [
        'slug'        => 'batman',
        'name'        => 'Batman',
        'description' => 'A hilariously simplified Dark Knight sporting a very smooth cowl.',
        'image_url'   => '/images/nfts/superhero/Batman.png',
    ],
    [
        'slug'        => 'cyborg',
        'name'        => 'Cyborg',
        'description' => 'Half-machine, half-derp, fully ready to save the day in vivid digital ink.',
        'image_url'   => '/images/nfts/superhero/Cyborg.png',
    ],
    [
        'slug'        => 'flash',
        'name'        => 'Flash',
        'description' => 'The fastest man alive, looking appropriately startled by his own speed.',
        'image_url'   => '/images/nfts/superhero/Flash.png',
    ],
    [
        'slug'        => 'ironman',
        'name'        => 'Iron Man',
        'description' => 'A wonderfully wobbly take on the iconic billionaire armored avenger.',
        'image_url'   => '/images/nfts/superhero/IronMan.png',
    ],
    [
        'slug'        => 'spiderman',
        'name'        => 'Spiderman',
        'description' => 'A spectacularly stretchy and thoroughly confused-looking web-slinger.',
        'image_url'   => '/images/nfts/superhero/Spiderman.png',
    ],
    [
        'slug'        => 'superman',
        'name'        => 'Superman',
        'description' => 'The Man of Steel, featuring an impressively elongated neck.',
        'image_url'   => '/images/nfts/superhero/Superman.png',
    ],
    [
        'slug'        => 'wonder-woman',
        'name'        => 'Wonder Woman',
        'description' => 'A fierce warrior princess rendered with charmingly off-kilter proportions.',
        'image_url'   => '/images/nfts/superhero/WonderWomen.png',
    ],
];

seedCollectionListings(
    $superhero,
    $superheroNfts,
    $collectionPricing['superhero-collection'],
    $editionsTotal,
    $sellerUserId,
    $vlasUser
);

// --- Geo Tennis NFTs ---
$geotennisNfts = [
    [
        'slug'        => 't1',
        'name'        => 't1',
        'description' => 'A dynamic tennis serve playfully interrupted by a bold, pale-yellow geometric block.',
        'image_url'   => '/images/nfts/geotennis/t1.png',
    ],
    [
        'slug'        => 't2',
        'name'        => 't2',
        'description' => 'Mid-stride on the hard court, heavily obscured by a mint-green censorship square.',
        'image_url'   => '/images/nfts/geotennis/t2.png',
    ],
    [
        'slug'        => 't3',
        'name'        => 't3',
        'description' => 'A powerful backhand stance partially hidden by a striking lime-green rectangle.',
        'image_url'   => '/images/nfts/geotennis/t3.png',
    ],
    [
        'slug'        => 't4',
        'name'        => 't4',
        'description' => 'Sprinting across the grass court, interrupted by an earthy olive-green box.',
        'image_url'   => '/images/nfts/geotennis/t4.png',
    ],
    [
        'slug'        => 't5',
        'name'        => 't5',
        'description' => 'A focused return shot blocked out by a stark, crisp white square over the torso.',
        'image_url'   => '/images/nfts/geotennis/t5.png',
    ],
    [
        'slug'        => 't6',
        'name'        => 't6',
        'description' => 'Diving for the ball on the grass, concealed by a muted maroon rectangular void.',
        'image_url'   => '/images/nfts/geotennis/t6.png',
    ],
    [
        'slug'        => 't7',
        'name'        => 't7',
        'description' => 'A soaring serve on clay, mysteriously censored by a warm terracotta block.',
        'image_url'   => '/images/nfts/geotennis/t7.png',
    ],
];

seedCollectionListings(
    $geotennis,
    $geotennisNfts,
    $collectionPricing['geotennis-collection'],
    $editionsTotal,
    $sellerUserId,
    $vlasUser
);

// --- Characters NFTs ---
$charactersNfts = [
    [
        'slug'        => 'carl',
        'name'        => 'carlos',
        'description' => 'A greyscale, slightly glitchy rendering of a familiar figure with wide-open arms.',
        'image_url'   => '/images/nfts/characters/carl.png',
    ],
    [
        'slug'        => 'him',
        'name'        => 'Him',
        'description' => 'A haunting, distorted portrait with a fractured, digital-brushstroke texture.',
        'image_url'   => '/images/nfts/characters/him.png',
    ],
    [
        'slug'        => 'lee',
        'name'        => 'Lee',
        'description' => 'A high-contrast, thermal-colored tribute to an iconic martial arts legend.',
        'image_url'   => '/images/nfts/characters/lee.png',
    ],
    [
        'slug'        => 'mads',
        'name'        => 'Mads',
        'description' => 'An intense, shadowed visage with digital artifacts dripping from the collar.',
        'image_url'   => '/images/nfts/characters/mads.png',
    ],
    [
        'slug'        => 'mike',
        'name'        => 'Mike',
        'description' => 'A heavily pixel-sorted, explosive portrait composed of scattered digital shards.',
        'image_url'   => '/images/nfts/characters/mike.png',
    ],
    [
        'slug'        => 'qqq',
        'name'        => 'QQQ',
        'description' => 'A surreal, wireframe-like mannequin featuring distinctly out-of-place realistic lips.',
        'image_url'   => '/images/nfts/characters/qqq.png',
    ],
    [
        'slug'        => 'box',
        'name'        => 'Box',
        'description' => 'A stark, high-contrast monochrome silhouette of a deeply shadowed physique.',
        'image_url'   => '/images/nfts/characters/box.png',
    ],
];

seedCollectionListings(
    $characters,
    $charactersNfts,
    $collectionPricing['characters-collection'],
    1,
    $sellerUserId,
    $vlasUser
);

// --- Fish NFTs ---
$fishNfts = [
    ['slug' => 'fish-1', 'name' => 'Abyssal Angler', 'description' => 'A mackerel soaring downward, watched over by an eerie, disembodied human eye.', 'image_url' => '/images/nfts/fish/fish1.png'],
    ['slug' => 'fish-2', 'name' => 'Neon Tetra', 'description' => 'A vertical fish composition topped with an unsettling, greyscale human gaze.', 'image_url' => '/images/nfts/fish/fish2.png'],
    ['slug' => 'fish-3', 'name' => 'Coral Crown', 'description' => 'A surreal aquatic dance featuring fish intertwined with a bold, painted human lip.', 'image_url' => '/images/nfts/fish/fish3.png'],
    ['slug' => 'fish-4', 'name' => 'Golden Koi', 'description' => 'A beautifully rendered blue fish sporting an unexpectedly human facial feature.', 'image_url' => '/images/nfts/fish/fish4.png'],
    ['slug' => 'fish-5', 'name' => 'Shadow Shark', 'description' => 'A bright red fish bearing a massive, unblinking cyclopean eye on its head.', 'image_url' => '/images/nfts/fish/fish5.png'],
    ['slug' => 'fish-6', 'name' => 'Beta Blaze', 'description' => 'A colorful aquatic creature swimming under the watchful gaze of a vivid blue eye.', 'image_url' => '/images/nfts/fish/fish6.png'],
    ['slug' => 'fish-7', 'name' => 'Crypto Puffer', 'description' => 'A heavily scaled composition featuring a strikingly realistic human eye with long lashes.', 'image_url' => '/images/nfts/fish/fish7.png'],
];

seedCollectionListings(
    $fish,
    $fishNfts,
    $collectionPricing['fish-collection'],
    $editionsTotal,
    $sellerUserId,
    $vlasUser
);

// --- Faces NFTs ---
$facesNfts = [
    ['slug' => 'face-1', 'name' => 'face1', 'description' => 'A heavily blurred monochrome portrait outlined with crude, nervous digital scribbles.', 'image_url' => '/images/nfts/faces/face1.png'],
    ['slug' => 'face-2', 'name' => 'face2', 'description' => 'A muted, out-of-focus subject defaced with whimsical, childish white line art.', 'image_url' => '/images/nfts/faces/face2.png'],
    ['slug' => 'face-3', 'name' => 'face3', 'description' => 'A vibrant red and orange blur sporting a hastily drawn, toothy smile.', 'image_url' => '/images/nfts/faces/face3.png'],
    ['slug' => 'face-4', 'name' => 'face4', 'description' => 'A soft-focus blue portrait detailed with bizarre, floating facial contours.', 'image_url' => '/images/nfts/faces/face4.png'],
    ['slug' => 'face-5', 'name' => 'face5', 'description' => 'A colorful blur rocking distinctively drawn shades and a squiggly pout.', 'image_url' => '/images/nfts/faces/face5.png'],
    ['slug' => 'face-6', 'name' => 'face6', 'description' => 'An ethereal, indistinct face overlaid with delicate, abstract facial mapping.', 'image_url' => '/images/nfts/faces/face6.png'],
    ['slug' => 'face-7', 'name' => 'face7', 'description' => 'A hazy, deep blue portrait featuring roughly sketched eyebrows and a gaping mouth.', 'image_url' => '/images/nfts/faces/face7.png'],
];

seedCollectionListings(
    $faces,
    $facesNfts,
    $collectionPricing['faces-collection'],
    $editionsTotal,
    $sellerUserId,
    $vlasUser
);

// --- Fruit Beasts NFTs ---
$fruitbeastsNfts = [
    [
        'slug' => 'berrysnout',
        'name' => 'Berrysnout',
        'description' => 'A jammy snouted beast bursting with berry-fuelled energy.',
        'image_url' => '/images/nfts/fruitbeasts/Berrysnout.jpg',
    ],
    [
        'slug' => 'citribbit',
        'name' => 'Citribbit',
        'description' => 'A zesty hopper with citrus skin and a mischievous grin.',
        'image_url' => '/images/nfts/fruitbeasts/Citribbit.jpg',
    ],
    [
        'slug' => 'jamhound',
        'name' => 'Jamhound',
        'description' => 'A sticky-sweet guardian hound with a fearless fruit-core bark.',
        'image_url' => '/images/nfts/fruitbeasts/Jamhound.jpg',
    ],
    [
        'slug' => 'lemonjaw',
        'name' => 'Lemonjaw',
        'description' => 'A sharp-toothed citrus creature with a bright sour attitude.',
        'image_url' => '/images/nfts/fruitbeasts/Lemonjaw.jpg',
    ],
    [
        'slug' => 'lemoo',
        'name' => 'Lemoo',
        'description' => 'A mellow orchard beast blending lemon charm with barnyard swagger.',
        'image_url' => '/images/nfts/fruitbeasts/Lemoo.jpg',
    ],
    [
        'slug' => 'pipsqueak',
        'name' => 'Pipsqueak',
        'description' => 'A tiny fruit beast with oversized personality and crunchy bite.',
        'image_url' => '/images/nfts/fruitbeasts/Pipsqueak.jpg',
    ],
    [
        'slug' => 'trotberry',
        'name' => 'Trotberry',
        'description' => 'A hoofed berry creature sprinting through the grove in vivid color.',
        'image_url' => '/images/nfts/fruitbeasts/Trotberry.jpg',
    ],
];

seedCollectionListings(
    $fruitbeasts,
    $fruitbeastsNfts,
    $collectionPricing['fruitbeasts-collection'],
    10,
    $specialUser->id,
    $specialUser
);

// --- AI Fruits NFTs ---
$aifruitsNfts = [
    [
        'slug' => 'ai-fruit-apple',
        'name' => 'AI Fruit Apple',
        'description' => 'A looping animated apple character with glossy AI-generated charm.',
        'image_url' => '/images/nfts/aifruits/ai-fruit-apple.gif',
    ],
    [
        'slug' => 'ai-fruit-banana',
        'name' => 'AI Fruit Banana',
        'description' => 'A playful animated banana brought to life with vibrant AI styling.',
        'image_url' => '/images/nfts/aifruits/ai-fruit-banana.gif',
    ],
    [
        'slug' => 'ai-fruit-kiwi',
        'name' => 'AI Fruit Kiwi',
        'description' => 'A fuzzy kiwi icon transformed into a lively animated collectible.',
        'image_url' => '/images/nfts/aifruits/ai-fruit-kiwi.gif',
    ],
    [
        'slug' => 'ai-fruit-pear',
        'name' => 'AI Fruit Pear',
        'description' => 'A smooth animated pear with a bright, synthetic personality.',
        'image_url' => '/images/nfts/aifruits/ai-fruit-pear.gif',
    ],
    [
        'slug' => 'ai-fruit-pineapple',
        'name' => 'AI Fruit Pineapple',
        'description' => 'A tropical pineapple character pulsing with playful motion.',
        'image_url' => '/images/nfts/aifruits/ai-fruit-pineapple.gif',
    ],
    [
        'slug' => 'ai-fruit-raspberry',
        'name' => 'AI Fruit Raspberry',
        'description' => 'A vivid raspberry animation with a candy-bright digital finish.',
        'image_url' => '/images/nfts/aifruits/ai-fruit-raspberry.gif',
    ],
    [
        'slug' => 'ai-fruit-watermelon',
        'name' => 'AI Fruit Watermelon',
        'description' => 'A juicy animated watermelon collectible with bold looping motion.',
        'image_url' => '/images/nfts/aifruits/ai-fruit-watermelon.gif',
    ],
];

seedCollectionListings(
    $aifruits,
    $aifruitsNfts,
    $collectionPricing['aifruits-collection'],
    $editionsTotal,
    $dariuszUser->id,
    $dariuszUser
);

// --- Hamster NFTs ---
$hamsterNfts = [
    [
        'slug' => 'gem-hamster',
        'name' => 'Gem Hamster',
        'description' => 'A sparkling one-of-one hamster with gem-like attitude.',
        'image_url' => '/images/nfts/hamster/gem-hamster.jpg',
    ],
    [
        'slug' => 'goofy-hamster',
        'name' => 'Goofy Hamster',
        'description' => 'A one-of-one hamster caught in peak goofy form.',
        'image_url' => '/images/nfts/hamster/goofy-hamster.jpg',
    ],
    [
        'slug' => 'happy-hamster',
        'name' => 'Happy Hamster',
        'description' => 'A one-of-one hamster radiating cheerful energy.',
        'image_url' => '/images/nfts/hamster/happy-hamster.jpg',
    ],
    [
        'slug' => 'hehe-hamster',
        'name' => 'Hehe Hamster',
        'description' => 'A one-of-one hamster with a mischievous little grin.',
        'image_url' => '/images/nfts/hamster/hehe-hasmter.jpg',
    ],
    [
        'slug' => 'hi-hamster',
        'name' => 'Hi Hamster',
        'description' => 'A one-of-one hamster frozen mid-greeting.',
        'image_url' => '/images/nfts/hamster/hi-hamster.jpg',
    ],
    [
        'slug' => 'homeless-hamster',
        'name' => 'Homeless Hamster',
        'description' => 'A one-of-one hamster portrait with rough-around-the-edges charm.',
        'image_url' => '/images/nfts/hamster/homeless-hamster.jpg',
    ],
    [
        'slug' => 'inlove-hamster',
        'name' => 'In Love Hamster',
        'description' => 'A one-of-one hamster wearing its heart on its sleeve.',
        'image_url' => '/images/nfts/hamster/inlove-hamster.jpg',
    ],
    [
        'slug' => 'just-a-girl-hamster',
        'name' => 'Just A Girl Hamster',
        'description' => 'A one-of-one hamster with pure main-character energy.',
        'image_url' => '/images/nfts/hamster/just-a-girl-hamster.jpg',
    ],
    [
        'slug' => 'silly-hamster',
        'name' => 'Silly Hamster',
        'description' => 'A one-of-one hamster leaning all the way into silliness.',
        'image_url' => '/images/nfts/hamster/silly-hamster.jpg',
    ],
    [
        'slug' => 'uhm-hamster',
        'name' => 'Uhm Hamster',
        'description' => 'A one-of-one hamster paused in a perfect awkward moment.',
        'image_url' => '/images/nfts/hamster/uhm-hamster.jpg',
    ],
];

seedCollectionListings(
    $hamster,
    $hamsterNfts,
    $collectionPricing['hamster-collection'],
    1,
    $almartsUser->id,
    $almartsUser
);

echo "Done.\n";