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
        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('form_id')->constrained()->onDelete('cascade');
            $table->string('submission_number')->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_company')->nullable();
            $table->enum('status', ['new', 'read', 'follow_up', 'done', 'archived', 'spam'])->default('new');
            $table->boolean('wa_notif_sent')->default(false);
            $table->timestamp('wa_notif_sent_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index(['form_id', 'status'], 'idx_submissions_user_status');
            $table->index(['form_id', 'submitted_at'], 'idx_submissions_submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
