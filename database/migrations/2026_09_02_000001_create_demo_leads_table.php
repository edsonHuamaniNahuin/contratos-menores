<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_leads', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('email', 150)->index();
            $table->string('empresa', 180)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('rubro', 180)->nullable();
            $table->string('origen', 250)->nullable()->comment('UTM o página de origen');
            $table->string('landing', 100)->nullable()->comment('Landing embudo E1/E2/E3');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_leads');
    }
};
