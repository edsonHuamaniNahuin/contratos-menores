<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_inbound_leads', function (Blueprint $table) {
            $table->id();
            $table->string('from_number', 30);
            $table->text('texto')->nullable();
            $table->boolean('primer_contacto')->default(false);
            $table->timestamps();

            $table->index('from_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_inbound_leads');
    }
};
