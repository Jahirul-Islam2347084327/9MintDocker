<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nfts_visibility')) {
                $column = $table->string('nfts_visibility', 20)->default('private');

                if (Schema::hasColumn('users', 'nfts_public')) {
                    $column->after('nfts_public');
                } elseif (Schema::hasColumn('users', 'search_public')) {
                    $column->after('search_public');
                }
            }

            if (! Schema::hasColumn('users', 'profile_comments_visibility')) {
                $column = $table->string('profile_comments_visibility', 20)->default('public');

                if (Schema::hasColumn('users', 'profile_comments_public')) {
                    $column->after('profile_comments_public');
                } elseif (Schema::hasColumn('users', 'nfts_visibility')) {
                    $column->after('nfts_visibility');
                } elseif (Schema::hasColumn('users', 'search_public')) {
                    $column->after('search_public');
                }
            }
        });

        if (Schema::hasColumn('users', 'nfts_public')) {
            DB::table('users')->update([
                'nfts_visibility' => DB::raw("CASE WHEN nfts_public = 1 THEN 'public' ELSE 'private' END"),
            ]);
        }

        if (Schema::hasColumn('users', 'profile_comments_public')) {
            DB::table('users')->update([
                'profile_comments_visibility' => DB::raw("CASE WHEN profile_comments_public = 1 THEN 'public' ELSE 'disabled' END"),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_comments_visibility')) {
                $table->dropColumn('profile_comments_visibility');
            }

            if (Schema::hasColumn('users', 'nfts_visibility')) {
                $table->dropColumn('nfts_visibility');
            }
        });
    }
};
