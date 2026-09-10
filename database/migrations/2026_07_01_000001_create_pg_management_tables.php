<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates all PG-management tables that were missing from the initial migrations:
 * rooms, room_amenities, beds, tenants, tenant_documents,
 * rent_agreements, agreement_signatures,
 * rent_bills, rent_payments, rent_razorpay_orders,
 * complaints, complaint_media, complaint_comments,
 * wishlists, token_payments
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Rooms ────────────────────────────────────────────────────────────
        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->string('room_number');
                $table->string('floor')->nullable();
                $table->enum('room_type', ['single', 'double', 'triple', 'quad', 'quint', 'dorm'])->default('double');
                $table->enum('gender', ['male', 'female', 'unisex'])->default('unisex');
                $table->decimal('monthly_rent', 10, 2)->default(0);
                $table->decimal('security_deposit', 10, 2)->default(0);
                $table->boolean('has_ac')->default(false);
                $table->boolean('has_attached_bathroom')->default(false);
                $table->boolean('has_balcony')->default(false);
                $table->boolean('has_geyser')->default(false);
                $table->boolean('has_wifi')->default(false);
                $table->text('notes')->nullable();
                $table->enum('status', ['available', 'full', 'maintenance'])->default('available');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['property_id', 'status']);
            });
        }

        // ── Room Amenities pivot ──────────────────────────────────────────────
        if (!Schema::hasTable('room_amenities')) {
            Schema::create('room_amenities', function (Blueprint $table) {
                $table->foreignId('room_id')->constrained()->cascadeOnDelete();
                $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
                $table->primary(['room_id', 'amenity_id']);
            });
        }

        // ── Beds ─────────────────────────────────────────────────────────────
        if (!Schema::hasTable('beds')) {
            Schema::create('beds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('room_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->string('bed_number');
                $table->enum('bed_type', ['single', 'bunk_lower', 'bunk_upper', 'double'])->default('single');
                $table->decimal('monthly_rent', 10, 2)->default(0);
                $table->enum('status', ['vacant', 'occupied', 'reserved', 'maintenance'])->default('vacant');
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->date('occupied_since')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['room_id', 'status']);
            });
        }

        // ── Tenants ──────────────────────────────────────────────────────────
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

                // Personal info
                $table->string('name');
                $table->string('phone', 20);
                $table->string('email')->nullable();
                $table->date('dob')->nullable();
                $table->enum('gender', ['male', 'female', 'other'])->nullable();
                $table->string('occupation')->nullable();
                $table->string('company_college')->nullable();

                // Permanent address
                $table->string('address_line')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('pincode', 10)->nullable();

                // Emergency contact
                $table->string('emergency_name')->nullable();
                $table->string('emergency_phone', 20)->nullable();
                $table->string('emergency_relation')->nullable();

                // Room/bed assignment
                $table->string('room_number')->nullable();
                $table->string('bed_number')->nullable();

                // Financial
                $table->decimal('monthly_rent', 10, 2)->default(0);
                $table->decimal('security_deposit', 10, 2)->default(0);

                // Dates
                $table->date('move_in_date')->nullable();
                $table->date('move_out_date')->nullable();
                $table->date('notice_date')->nullable();
                $table->string('notice_reason')->nullable();

                // KYC
                $table->enum('kyc_status', ['pending', 'submitted', 'approved', 'rejected'])->default('pending');
                $table->text('kyc_remarks')->nullable();
                $table->timestamp('kyc_reminder_sent_at')->nullable();
                $table->string('onboarding_source')->nullable();

                // Aadhaar e-KYC
                $table->string('aadhaar_number_masked', 20)->nullable();
                $table->string('aadhaar_name')->nullable();
                $table->timestamp('aadhaar_verified_at')->nullable();

                // Agreement
                $table->timestamp('agreement_signed_at')->nullable();
                $table->string('agreement_ip')->nullable();

                // Status & notes
                $table->enum('status', ['active', 'vacated', 'notice_period', 'blacklisted'])->default('active');
                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index(['owner_id', 'status']);
                $table->index('phone');
            });
        }

        // ── Tenant Documents ─────────────────────────────────────────────────
        if (!Schema::hasTable('tenant_documents')) {
            Schema::create('tenant_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('document_type'); // aadhaar_front, aadhaar_back, pan, photo, etc.
                $table->string('file_path');
                $table->string('file_name')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('mime_type')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'document_type']);
            });
        }

        // ── Rent Agreements ──────────────────────────────────────────────────
        if (!Schema::hasTable('rent_agreements')) {
            Schema::create('rent_agreements', function (Blueprint $table) {
                $table->id();
                $table->string('agreement_number')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

                // Room/bed reference (denormalised for PDF generation)
                $table->string('room_number')->nullable();
                $table->string('bed_number')->nullable();

                // Financial
                $table->decimal('monthly_rent', 10, 2);
                $table->decimal('security_deposit', 10, 2)->default(0);
                $table->decimal('maintenance_fee', 10, 2)->default(0);
                $table->boolean('electricity_included')->default(false);
                $table->boolean('water_included')->default(false);
                $table->boolean('food_included')->default(false);

                // Term
                $table->date('start_date');
                $table->date('end_date');
                $table->unsignedTinyInteger('lock_in_months')->default(0);
                $table->unsignedTinyInteger('notice_period_days')->default(30);
                $table->unsignedTinyInteger('rent_due_day')->default(5);

                // Content
                $table->text('terms_template')->nullable();
                $table->text('additional_terms')->nullable();
                $table->text('house_rules')->nullable();

                // Status & lifecycle
                $table->enum('status', [
                    'draft', 'sent', 'signed_tenant', 'signed_owner',
                    'active', 'expired', 'terminated', 'renewed'
                ])->default('draft');

                $table->timestamp('signed_at')->nullable();
                $table->timestamp('terminated_at')->nullable();
                $table->text('termination_reason')->nullable();
                $table->foreignId('parent_agreement_id')->nullable()
                    ->constrained('rent_agreements')->nullOnDelete();
                $table->timestamp('renewal_reminded_at')->nullable();
                $table->text('notes')->nullable();

                // E-sign fields (Setu / DigiLocker integration)
                $table->string('esign_status')->nullable(); // pending, initiated, completed, failed
                // NOTE: setu_download_url, signature_initiated_at, signature_completed_at
                // are added by migration 2026_08_04_000001_add_esign_fields_to_rent_agreements

                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['owner_id', 'status']);
            });
        }

        // ── Agreement Signatures ─────────────────────────────────────────────
        if (!Schema::hasTable('agreement_signatures')) {
            Schema::create('agreement_signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('rent_agreements')->cascadeOnDelete();
                $table->enum('signer_type', ['owner', 'tenant', 'witness']);
                $table->string('signer_name')->nullable();
                $table->unsignedBigInteger('signer_id')->nullable(); // user_id
                $table->longText('signature_data')->nullable(); // base64 image or hash
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->timestamps();

                $table->index(['agreement_id', 'signer_type']);
            });
        }

        // ── Rent Bills ───────────────────────────────────────────────────────
        // NOTE: soft deletes added by 2026_08_25_070001_add_soft_deletes_to_rent_bills_table
        if (!Schema::hasTable('rent_bills')) {
            Schema::create('rent_bills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

                $table->string('bill_number')->unique();
                $table->string('month', 7); // YYYY-MM

                // Charge breakdown
                $table->decimal('rent_amount', 10, 2)->default(0);
                $table->decimal('electricity', 10, 2)->default(0);
                $table->decimal('water', 10, 2)->default(0);
                $table->decimal('maintenance', 10, 2)->default(0);
                $table->decimal('food_charges', 10, 2)->default(0);
                $table->decimal('other_charges', 10, 2)->default(0);
                $table->string('other_charges_label')->nullable();
                $table->decimal('late_fee', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);

                // Totals
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->decimal('due_amount', 10, 2)->default(0);

                $table->date('due_date');
                $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
                $table->timestamp('last_reminder_sent_at')->nullable();
                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index(['tenant_id', 'month']);
                $table->index(['owner_id', 'status']);
            });
        }

        // ── Rent Payments ────────────────────────────────────────────────────
        if (!Schema::hasTable('rent_payments')) {
            Schema::create('rent_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rent_bill_id')->constrained('rent_bills')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

                $table->string('receipt_number')->unique();
                $table->decimal('amount', 10, 2);
                $table->enum('payment_method', [
                    'cash', 'upi', 'bank_transfer', 'razorpay',
                    'phonepe', 'paytm', 'cheque', 'other'
                ])->default('cash');
                $table->string('transaction_ref')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index('rent_bill_id');
            });
        }

        // ── Rent Razorpay Orders ─────────────────────────────────────────────
        if (!Schema::hasTable('rent_razorpay_orders')) {
            Schema::create('rent_razorpay_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rent_bill_id')->constrained('rent_bills')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('razorpay_order_id')->nullable()->unique();
                $table->string('razorpay_payment_id')->nullable();
                $table->string('razorpay_signature')->nullable();
                $table->enum('status', ['created', 'paid', 'failed', 'refunded'])->default('created');
                $table->text('failure_reason')->nullable();
                $table->timestamps();
            });
        }

        // ── Complaints ───────────────────────────────────────────────────────
        if (!Schema::hasTable('complaints')) {
            Schema::create('complaints', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

                $table->enum('category', [
                    'plumbing', 'electrical', 'wifi', 'housekeeping',
                    'food', 'furniture', 'security', 'ac', 'water', 'other'
                ])->default('other');

                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->string('title');
                $table->text('description');

                $table->enum('status', [
                    'open', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled'
                ])->default('open');

                // Assignment
                $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('assigned_to_name')->nullable();
                $table->string('assigned_to_phone', 20)->nullable();
                $table->timestamp('assigned_at')->nullable();

                // Resolution
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();

                // Feedback
                $table->tinyInteger('rating')->nullable();
                $table->text('feedback')->nullable();

                $table->timestamps();

                $table->index(['property_id', 'status']);
                $table->index(['tenant_id', 'status']);
            });
        }

        // ── Complaint Media ──────────────────────────────────────────────────
        if (!Schema::hasTable('complaint_media')) {
            Schema::create('complaint_media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
                $table->enum('media_type', ['image', 'video', 'document'])->default('image');
                $table->string('file_path');
                $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // ── Complaint Comments ───────────────────────────────────────────────
        if (!Schema::hasTable('complaint_comments')) {
            Schema::create('complaint_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('author_name')->nullable();
                $table->string('author_role')->nullable();
                $table->text('comment');
                $table->boolean('is_internal')->default(false); // internal staff notes
                $table->timestamps();
            });
        }

        // wishlists already created in 2024_01_01_000004_create_leads_visits_table

        // ── Token Payments (field executive token collection) ─────────────────
        if (!Schema::hasTable('token_payments')) {
            Schema::create('token_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('user_id')->nullable();   // tenant's user account
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('amount', 10, 2);
                $table->enum('payment_method', ['cash', 'upi', 'razorpay', 'other'])->nullable();
                $table->string('razorpay_order_id')->nullable();
                $table->string('razorpay_payment_id')->nullable();
                $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
                $table->unsignedBigInteger('collected_by')->nullable(); // field exec user id
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('token_payments');
        // wishlists handled by 2024_01_01_000004
        Schema::dropIfExists('complaint_comments');
        Schema::dropIfExists('complaint_media');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('rent_razorpay_orders');
        Schema::dropIfExists('rent_payments');
        Schema::dropIfExists('rent_bills');
        Schema::dropIfExists('agreement_signatures');
        Schema::dropIfExists('rent_agreements');
        Schema::dropIfExists('tenant_documents');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('room_amenities');
        Schema::dropIfExists('rooms');
    }
};
