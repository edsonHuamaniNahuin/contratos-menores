<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_type', ['personal', 'empresa'])->default('personal')->after('name');
            $table->string('ruc', 11)->nullable()->after('account_type');
            $table->string('razon_social')->nullable()->after('ruc');
            $table->string('telefono', 20)->nullable()->after('razon_social');

            $table->index('account_type');
            $table->index('ruc');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type']);
            $table->dropIndex(['ruc']);
            $table->dropColumn(['account_type', 'ruc', 'razon_social', 'telefono']);
        });
    }
};
