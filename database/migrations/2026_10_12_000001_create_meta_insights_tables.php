<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_insights_diarios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('ad_id', 40);
            $table->string('ad_name', 200)->nullable();
            $table->string('adset_id', 40)->nullable();
            $table->string('adset_name', 200)->nullable();
            $table->string('campaign_id', 40)->nullable();
            $table->string('campaign_name', 200)->nullable();
            $table->decimal('spend', 10, 2)->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('link_clicks')->default(0);
            $table->unsignedInteger('leads')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('cpc', 8, 2)->default(0);
            $table->decimal('cpm', 8, 2)->default(0);
            $table->decimal('frequency', 8, 4)->default(0);
            $table->timestamps();

            $table->unique(['fecha', 'ad_id']);
            $table->index('adset_id');
        });

        Schema::create('meta_insights_intradia', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->dateTime('capturado_at');
            $table->string('ad_id', 40);
            $table->string('ad_name', 200)->nullable();
            $table->string('adset_id', 40)->nullable();
            $table->string('adset_name', 200)->nullable();
            $table->decimal('spend', 10, 2)->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('link_clicks')->default(0);
            $table->unsignedInteger('leads')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('cpc', 8, 2)->default(0);
            $table->decimal('cpm', 8, 2)->default(0);
            $table->decimal('frequency', 8, 4)->default(0);
            $table->timestamps();

            $table->unique(['capturado_at', 'ad_id']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_insights_intradia');
        Schema::dropIfExists('meta_insights_diarios');
    }
};
