<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seller_profile_feedback')) {
            return;
        }

        Schema::create('seller_profile_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('comment_type', 20)->nullable();
            $table->text('body')->nullable();
            $table->timestamp('deleted_by_owner_at')->nullable();
            $table->foreignId('deleted_by_owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['seller_user_id', 'created_at']);
            $table->index(['seller_user_id', 'deleted_by_owner_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profile_feedback');
    }
};
