<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('utm_source', 100)->nullable()->after('telefono');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 150)->nullable()->after('utm_medium');
            $table->string('utm_content', 150)->nullable()->after('utm_campaign');
            $table->string('utm_term', 150)->nullable()->after('utm_content');
            $table->string('fbclid', 255)->nullable()->after('utm_term');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid']);
        });
    }
};
