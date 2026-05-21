<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add the new Google ID column after the email column
            $table->string('google_id')->nullable()->unique()->after('email');
            
            // Make the existing password column optional (Google users don't use passwords)
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
            // Revert password back to being required
            $table->string('password')->nullable(false)->change();
        });
    }
};
