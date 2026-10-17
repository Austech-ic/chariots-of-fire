<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operational records: counselling, appointments, donations, communications, members.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counselling_services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedSmallInteger('buffer_minutes')->default(15);
            $table->string('booking_mode', 20)->default('both'); // request, self_service, both
            $table->boolean('approval_required')->default(true);
            $table->boolean('requires_account')->default(false);
            $table->json('appointment_methods')->nullable(); // in_person, phone, online
            $table->unsignedSmallInteger('min_notice_hours')->default(24);
            $table->unsignedSmallInteger('max_advance_days')->default(30);
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('counsellor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('booking_enabled')->default(true);
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->timestamps();
        });

        Schema::create('counselling_service_counsellor', function (Blueprint $table) {
            $table->foreignId('counselling_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counsellor_profile_id')->constrained()->cascadeOnDelete();
            $table->primary(['counselling_service_id', 'counsellor_profile_id'], 'service_counsellor_primary');
        });

        Schema::create('counselling_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('counselling_services')->nullOnDelete();
            $table->text('contact_data'); // encrypted JSON: name, email, phone
            $table->string('preferred_contact', 20)->default('email');
            $table->string('category', 60)->nullable();
            $table->text('encrypted_message');
            $table->text('preferred_times')->nullable(); // encrypted
            $table->string('appointment_method', 20)->nullable();
            $table->string('status', 20)->default('new')->index(); // new, assigned, offered, scheduled, closed
            $table->foreignId('assigned_to')->nullable()->constrained('counsellor_profiles')->nullOnDelete();
            $table->text('internal_notes')->nullable(); // encrypted
            $table->string('response_token', 64)->nullable()->unique();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counsellor_id')->constrained('counsellor_profiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0 = Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->index(['counsellor_id', 'weekday']);
        });

        Schema::create('availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counsellor_id')->constrained('counsellor_profiles')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('exception_type', 20)->default('blocked'); // blocked, leave
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['counsellor_id', 'starts_at', 'ends_at']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('counselling_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('counsellor_id')->constrained('counsellor_profiles')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('counselling_services')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('contact_data'); // encrypted JSON
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->string('method', 20)->default('in_person');
            $table->string('status', 20)->default('pending'); // offered, pending, confirmed, cancelled, completed, no_show
            $table->string('source', 20)->default('self_service'); // self_service, staff
            $table->string('manage_token', 64)->unique();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['counsellor_id', 'starts_at', 'ends_at', 'status'], 'appointments_slot_index');
        });

        Schema::create('donation_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('target_minor')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('donation_campaigns')->nullOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('frequency', 20)->default('one_time');
            $table->string('status', 20)->default('pending')->index(); // pending, paid, failed, abandoned, refunded, partially_refunded, reversed
            $table->string('provider', 30);
            $table->string('provider_transaction_id')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('receipt_sent_at')->nullable();
            $table->string('reconciliation_status', 20)->default('unreconciled');
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_transaction_id']);
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key')->unique();
            $table->string('provider_reference')->nullable()->index();
            $table->string('checkout_url', 1000)->nullable();
            $table->string('status', 20)->default('initiated'); // initiated, succeeded, failed, abandoned
            $table->string('source', 20)->default('checkout'); // checkout, webhook, verification, reconciliation
            $table->unsignedBigInteger('amount_minor')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('provider_status')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('donation_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->string('provider_reference')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->string('status', 20)->default('pending');
            $table->string('reason', 500)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email_normalized')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('status', 20)->default('pending'); // pending, active, unsubscribed, suppressed
            $table->string('locale', 10)->default('en');
            $table->string('confirmation_token', 64)->nullable()->unique();
            $table->string('provider_sync_status', 20)->default('not_synced');
            $table->text('provider_sync_error')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('newsletter_subscriptions')->cascadeOnDelete();
            $table->string('purpose', 60);
            $table->string('source', 60);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('granted_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('contact_hash', 64)->index();
            $table->text('contact_value'); // encrypted
            $table->string('channel', 20); // sms, whatsapp
            $table->string('purpose', 60); // event_updates, appointment_reminders, ministry_news
            $table->string('status', 20)->default('granted'); // granted, withdrawn
            $table->string('source', 60)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['contact_hash', 'channel', 'purpose']);
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20);
            $table->string('provider', 40);
            $table->string('template', 80);
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('recipient_hash', 64)->nullable();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('status', 20)->default('queued'); // queued, sent, delivered, failed, skipped
            $table->string('provider_message_id')->nullable()->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('member_resources', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('external_url')->nullable();
            $table->string('access_policy', 20)->default('all_members'); // all_members, granted_only
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('member_resource_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('member_resources')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->unique(['resource_id', 'user_id']);
        });

        Schema::create('account_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // deactivation, deletion, data_export, correction
            $table->text('details')->nullable();
            $table->string('status', 20)->default('open');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'account_requests', 'member_resource_access', 'member_resources', 'contact_enquiries',
            'notification_logs', 'notification_consents', 'newsletter_consents', 'newsletter_subscriptions',
            'donation_refunds', 'payment_attempts', 'donations', 'donation_campaigns', 'appointments',
            'availability_exceptions', 'availability_rules', 'counselling_requests',
            'counselling_service_counsellor', 'counsellor_profiles', 'counselling_services',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
