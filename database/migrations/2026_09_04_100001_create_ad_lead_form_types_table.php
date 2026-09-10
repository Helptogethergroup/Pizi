<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ad_lead_form_types')) {
            return;
        }

        Schema::create('ad_lead_form_types', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['meta', 'google']);
            $table->string('form_id', 100);
            $table->string('label')->nullable(); // e.g. "Owner - List your PG"
            $table->enum('inquiry_type', ['tenant', 'owner']);
            $table->timestamps();

            $table->unique(['platform', 'form_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_lead_form_types');
    }
};
