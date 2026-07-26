<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pago_yapes', function (Blueprint $table) {
            $table->string('tipo')->default('nuevo')->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('pago_yapes', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
