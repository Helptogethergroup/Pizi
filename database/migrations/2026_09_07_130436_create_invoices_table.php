<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('credit_package_id')->nullable()->constrained('credit_packages')->nullOnDelete();
            $table->string('title'); // e.g. package name, or manual description
            $table->decimal('total_amount', 10, 2); // GST-inclusive final amount
            $table->decimal('gst_rate', 5, 2)->default(18.00);
            $table->decimal('base_amount', 10, 2); // total_amount / (1 + gst_rate/100)
            $table->decimal('gst_amount', 10, 2); // total_amount - base_amount
            $table->enum('type', ['auto', 'manual'])->default('auto');
            $table->string('pdf_path')->nullable();
            $table->boolean('sent_via_whatsapp')->default(false);
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // admin, for manual invoices
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
