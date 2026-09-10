<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chat_sessions')) {
            Schema::create('chat_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->unique();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->enum('language', ['en', 'hi'])->default('en');
                $table->string('customer_phone')->nullable();
                $table->string('customer_name')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();
                $table->index('session_id');
            });
        }

        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_session_id')->constrained('chat_sessions')->onDelete('cascade');
                $table->enum('sender_type', ['customer', 'ai'])->default('customer');
                $table->longText('message');
                $table->string('language')->default('en');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index('chat_session_id');
            });
        }

        if (!Schema::hasTable('chat_templates')) {
            Schema::create('chat_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('response_en');
                $table->longText('response_hi');
                $table->string('category');
                $table->integer('priority')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->string('question_en');
                $table->string('question_hi');
                $table->longText('answer_en');
                $table->longText('answer_hi');
                $table->string('category');
                $table->integer('views')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('chat_templates');
        Schema::dropIfExists('faqs');
    }
};