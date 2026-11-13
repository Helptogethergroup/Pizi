<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Notices an owner (or PG Manager) posts for the people living in a PG; shown in the tenant app. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('property_notices')) {
            return;
        }

        Schema::create('property_notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->index();
            // null = every PG of this owner
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('title', 150);
            $table->text('body');
            $table->string('kind', 20)->default('notice'); // notice | urgent | menu | rule
            $table->boolean('pinned')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'property_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_notices');
    }
};
