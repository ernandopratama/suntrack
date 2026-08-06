<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notification Logs — 6-status delivery tracking for WhatsApp, Email, and In-App channels (Sprint 11 ADR-029).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Channel & recipient
            $table->string('type', 50)->comment('whatsapp | email | in_app | sms');
            $table->string('recipient')->comment('Phone number, email address, or user_id');

            // Message content
            $table->string('subject')->nullable()->comment('Email subject or notification title');
            $table->text('body');

            // Delivery status — 6-stage lifecycle (ADR-029)
            // pending → processing → sent → delivered → failed / cancelled
            $table->string('status', 30)->default('pending');

            // Retry tracking
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(3);

            // Timestamps per status transition
            $table->dateTime('processing_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('scheduled_at')->nullable()->comment('For future scheduled notifications');

            // Error details
            $table->text('failure_reason')->nullable();

            // Metadata (gateway response, message ID, webhook data)
            $table->json('metadata')->nullable();

            // Polymorphic source reference
            $table->nullableUuidMorphs('notifiable');

            // Indexes
            $table->index(['type', 'status'], 'notif_type_status_idx');
            $table->index('status', 'notif_status_idx');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
