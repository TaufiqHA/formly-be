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
        Schema::create('submission_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('form_field_id')->constrained()->onDelete('cascade');
            $table->string('field_label');
            $table->text('value_text')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->index('submission_id', 'idx_submission_values_sub');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_values');
    }
};
