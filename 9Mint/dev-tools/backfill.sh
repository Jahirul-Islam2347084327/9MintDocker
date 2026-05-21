#!/bin/bash

# ============================================
#   NFT Thumbnail Backfill Tool
#   Rebuilds missing/deleted thumbnails
# ============================================

# Get the directory where the script is located
# Equivalent to %~dp0 in Windows
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

echo "Collections:"
echo "   0. All collections"

# Run Laravel Tinker to list collections
php artisan tinker --execute="App\Models\Collection::query()->orderBy('id')->get(['id','name'])->each(function(\$c){echo '  '.\$c->id.'. '.\$c->name.PHP_EOL;});"

echo ""
read -p "Type collection number (0 = all): " COLLECTION_CHOICE
COLLECTION_CHOICE=${COLLECTION_CHOICE:-0}

echo ""
echo "Run mode:"
echo "   1. Missing only (also repairs deleted thumbnail files)"
echo "   2. Force re-generate all in selected scope"
read -p "Choose mode [1/2] (default 1): " MODE_CHOICE
MODE_CHOICE=${MODE_CHOICE:-1}

# Build the command string
CMD="php artisan nfts:backfill-thumbnails"

if [ "$COLLECTION_CHOICE" != "0" ]; then
    CMD="$CMD --collection-id=$COLLECTION_CHOICE"
fi

if [ "$MODE_CHOICE" == "2" ]; then
    CMD="$CMD --force"
    
    echo ""
    echo "Clearing existing thumbnails in selected scope..."
    
    # Exporting variable so tinker can see it via getenv
    export COLLECTION_CHOICE
    
    php artisan tinker --execute="\$cid=(int)getenv('COLLECTION_CHOICE');\$q=App\Models\Nft::query();if(\$cid>0){\$q->where('collection_id',\$cid);} \$dirs=[];\$q->get(['image_url','thumbnail_url'])->each(function(\$n)use(&\$dirs){foreach([\$n->image_url,\$n->thumbnail_url] as \$p){if(!is_string(\$p)||trim(\$p)===''){continue;}\$p=str_replace('\\\\','/',\$p);\$p=ltrim(\$p,'/');\$d=trim(dirname(\$p),'.');if(\$d===''){continue;}\$dirs[(substr(\$d,-7)==='/thumbs')?\$d:(\$d.'/thumbs')]=true;}});\$deleted=0;\$scanned=0;foreach(array_keys(\$dirs) as \$d){\$abs=public_path(\$d);if(!is_dir(\$abs)){continue;}\$scanned++;foreach(glob(\$abs.'/*')?:[] as \$f){if(is_file(\$f)){@unlink(\$f);\$deleted++;}}}echo 'Cleared '.\$deleted.' file(s) across '.\$scanned.' thumbnail folder(s).'.PHP_EOL;"
fi

echo ""
echo "Running: $CMD"
eval $CMD

echo ""
read -n 1 -s -r -p "Done. Press any key to close."
echo ""
