<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrandar codigo_snip: el API SEACE devuelve listas largas de projectIDs
     * (hasta 2000+ chars). varchar(50) causaba "Data too long" en la importación.
     */
    public function up(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->string('codigo_snip', 2000)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->string('codigo_snip', 50)->nullable()->change();
        });
    }
};
