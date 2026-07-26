<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pago_yapes', function (Blueprint $table) {
            $table->string('nombre_original')->nullable()->after('comprobante');
            $table->string('comprobante_dir')->nullable()->after('nombre_original');
        });
    }

    public function down(): void
    {
        Schema::table('pago_yapes', function (Blueprint $table) {
            $table->dropColumn(['nombre_original', 'comprobante_dir']);
        });
    }
};
