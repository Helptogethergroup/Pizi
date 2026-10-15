<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Money held as security deposit: what came in, what was cut, what went back.
        if (!Schema::hasTable('deposit_entries')) {
            Schema::create('deposit_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('tenant_id');
                $table->string('type', 20); // received | deduction | refund
                $table->decimal('amount', 10, 2);
                $table->date('entry_date');
                $table->string('note', 255)->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'entry_date']);
                $table->index('owner_id');
            });
        }

        // Move-in / move-out checklist: one row per tenant and kind.
        if (!Schema::hasTable('tenant_checklists')) {
            Schema::create('tenant_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('tenant_id');
                $table->string('kind', 20); // move_in | move_out
                $table->json('items'); // [{label, done, note}]
                $table->string('notes', 500)->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'kind']);
                $table->index('owner_id');
            });
        }

        // Electricity meter readings, so the next bill knows where the last one stopped.
        if (!Schema::hasTable('meter_readings')) {
            Schema::create('meter_readings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('tenant_id');
                $table->decimal('reading', 10, 2);
                $table->date('read_on');
                $table->timestamps();

                $table->index(['tenant_id', 'read_on']);
                $table->index('owner_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
        Schema::dropIfExists('tenant_checklists');
        Schema::dropIfExists('deposit_entries');
    }
};
