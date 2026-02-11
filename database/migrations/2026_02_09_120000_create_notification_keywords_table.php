<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('es_publico')->default(true);
            $table->timestamps();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_keywords');
    }

    private function seedDefaults(): void
    {
        $defaults = [
            'Consultoría legal',
            'Soporte tecnológico',
            'Servicios logísticos',
            'Obras civiles',
            'Suministro médico',
            'Seguridad y vigilancia',
            'Limpieza integral',
            'Capacitación corporativa',
        ];

        foreach ($defaults as $nombre) {
            DB::table('notification_keywords')->insert([
                'nombre' => $nombre,
                'slug' => Str::slug($nombre),
                'descripcion' => 'Keyword inicial',
                'es_publico' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
