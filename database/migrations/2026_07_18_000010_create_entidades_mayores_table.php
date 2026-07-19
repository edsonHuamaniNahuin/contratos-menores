<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entidades_mayores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique()->comment('Nombre de la entidad (buyer.name del OCDS)');
            $table->string('ruc', 11)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entidades_mayores');
    }
};
