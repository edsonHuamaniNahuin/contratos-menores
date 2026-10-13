<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_events', function (Blueprint $table) {
            $table->id();
            $table->string('landing', 100)->nullable();
            $table->string('tipo', 20); // click | scroll
            $table->decimal('x_pct', 6, 2)->nullable();
            $table->decimal('y_pct', 6, 2)->nullable();
            $table->unsignedTinyInteger('scroll_pct')->nullable();
            $table->string('target', 120)->nullable();
            $table->unsignedSmallInteger('vw')->nullable();
            $table->unsignedSmallInteger('vh')->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_content', 150)->nullable();
            $table->string('fbclid', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['landing', 'tipo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_events');
    }
};
