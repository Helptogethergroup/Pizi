<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * food_timing ab structured JSON store karta hai — har meal
     * (breakfast/lunch/dinner) ki apni timing + kis din milta hai
     * (all / weekdays / weekends / none). 255 chars kaafi nahi tha.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->text('food_timing')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('food_timing', 255)->nullable()->change();
        });
    }
};
