<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'profile_comments_public')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $column = $table->boolean('profile_comments_public')->default(true);

            if (Schema::hasColumn('users', 'search_public')) {
                $column->after('search_public');
            } elseif (Schema::hasColumn('users', 'nfts_public')) {
                $column->after('nfts_public');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_comments_public')) {
                $table->dropColumn('profile_comments_public');
            }
        });
    }
};
