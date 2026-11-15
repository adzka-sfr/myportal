<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false)->after('password');
            $table->boolean('is_active')->default(true)->after('is_superadmin');
        });

        Schema::create('user_application_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('application_slug', 50);
            $table->timestamps();
            $table->unique(['user_id', 'application_slug']);
        });

        Schema::create('user_feature_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('application_slug', 50);
            $table->string('feature_slug', 50);
            $table->timestamps();
            $table->unique(['user_id', 'application_slug', 'feature_slug']);
        });

        Schema::create('shopping_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->index(['user_id', 'name']);
        });

        Schema::create('shopping_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_list_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('quantity', 60)->nullable();
            $table->boolean('is_checked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_items');
        Schema::dropIfExists('shopping_lists');
        Schema::dropIfExists('user_feature_access');
        Schema::dropIfExists('user_application_access');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_superadmin', 'is_active']);
        });
    }
};
