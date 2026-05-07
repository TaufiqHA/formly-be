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
        Schema::create('form_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('form_id')->constrained()->onDelete('cascade');
            $table->string('label');
            $table->enum('field_type', ['text', 'para', 'drop', 'check', 'radio', 'email', 'phone', 'address']);
            $table->string('placeholder')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable(); // Using json for broader compatibility, TDD says JSONB (PostgreSQL)
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['form_id', 'sort_order'], 'idx_form_fields_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
