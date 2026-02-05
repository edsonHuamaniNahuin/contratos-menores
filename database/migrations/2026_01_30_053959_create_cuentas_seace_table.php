<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuentas_seace', function (Blueprint $table) {
            $table->id();

            // Información de la cuenta
            $table->string('nombre')->comment('Nombre descriptivo de la cuenta');
            $table->string('username')->unique()->comment('DNI o RUC del proveedor (10-11 dígitos)');
            $table->text('password')->comment('Contraseña encriptada con encrypt()');
            $table->string('email')->nullable()->comment('Email del usuario para notificaciones');

            // Tokens de sesión (el sistema maneja ambos)
            $table->text('access_token')->nullable()->comment('Token JWT (expira en 5 minutos)');
            $table->text('refresh_token')->nullable()->comment('UUID de refresco (el servidor devuelve uno nuevo en cada refresh)');
            $table->timestamp('token_expires_at')->nullable()->comment('Fecha de expiración del access_token');

            // Control de estado
            $table->boolean('activa')->default(false)->comment('Solo una cuenta activa a la vez');
            $table->timestamp('last_login_at')->nullable()->comment('Última vez que se hizo login completo');

            // Auditoría
            $table->timestamps();

            // Índices
            $table->index('activa');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_seace');
    }
};
