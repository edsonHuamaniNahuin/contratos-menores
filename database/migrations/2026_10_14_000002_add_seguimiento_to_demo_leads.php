<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_leads', function (Blueprint $table) {
            $table->timestamp('seguimiento_enviado_at')->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('demo_leads', function (Blueprint $table) {
            $table->dropColumn('seguimiento_enviado_at');
        });
    }
};
