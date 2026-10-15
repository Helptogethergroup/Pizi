<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('owner_expenses')) {
            return;
        }

        Schema::create('owner_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            // Which PG it was for. Null means "all of my PGs".
            $table->unsignedBigInteger('property_id')->nullable();
            $table->string('category', 40);
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_expenses');
    }
};
