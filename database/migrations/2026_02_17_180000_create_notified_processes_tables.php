<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para el sistema de tracking de notificaciones basado en BD.
 *
 * Reemplaza el dedup por caché (tdr:procesados:YYYY-MM-DD) con un sistema
 * persistente que permite:
 *   1. Dedup per-usuario (no global) → cambiar keywords surte efecto inmediato
 *   2. Vista "Mis Procesos Notificados" para el usuario
 *   3. Re-notificación bajo demanda (Telegram, WhatsApp, Email)
 *
 * Arquitectura normalizada:
 *   notified_processes (1 fila por proceso SEACE único)
 *     └── notification_sends (N filas: user × canal × recipient)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Tabla maestra: un registro por proceso SEACE único ────────
        Schema::create('notified_processes', function (Blueprint $table) {
            $table->id();

            // Identificador único del proceso en SEACE (idContrato, desContratacion, etc.)
            $table->string('seace_proceso_id', 100)->unique();

            // Datos desnormalizados para display rápido (evitar deserializar payload)
            $table->string('codigo', 255)->nullable()
                ->comment('desContratacion / código del proceso');
            $table->string('entidad', 500)->nullable()
                ->comment('nomEntidad');
            $table->text('descripcion')->nullable()
                ->comment('desObjetoContrato / nomObjetoContrato');
            $table->string('monto_referencial', 50)->nullable()
                ->comment('montoReferencial');
            $table->string('fecha_publicacion', 30)->nullable()
                ->comment('fecPublica (formato SEACE original)');
            $table->string('objeto_contratacion', 100)->nullable()
                ->comment('nomObjetoContrato (Bien, Servicio, Obra, etc.)');

            // Payload completo para re-notificación
            $table->json('payload')
                ->comment('Datos crudos del SEACE para reenvío');

            $table->timestamps();

            // Índice para consultas por fecha
            $table->index('fecha_publicacion', 'idx_notified_fecha_pub');
            $table->index('created_at', 'idx_notified_created');
        });

        // ── Tabla pivot: cada envío de notificación ──────────────────
        Schema::create('notification_sends', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notified_process_id')
                ->constrained('notified_processes')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Canal: telegram, whatsapp, email
            $table->string('canal', 20);

            // Identificador del destinatario (chat_id, phone_number, email)
            // Almacenado como string plano (desnormalizado) para independencia
            // de la tabla de suscripciones → si se borra la suscripción, el
            // registro de envío permanece intacto.
            $table->string('recipient_id', 100);

            // Etiqueta legible de la suscripción (nombre del bot, alias, etc.)
            // Desnormalizado: no depende de FK a suscripción.
            $table->string('subscription_label', 255)->nullable();

            // Keywords que coincidieron en este envío
            $table->json('keywords_matched')->nullable();

            // Momento exacto del envío
            $table->timestamp('notified_at');

            $table->timestamp('created_at')->nullable();

            // ── Constraints ──────────────────────────────────────────
            // Evita envíos duplicados: mismo proceso → mismo usuario → mismo canal → mismo recipient
            $table->unique(
                ['notified_process_id', 'user_id', 'canal', 'recipient_id'],
                'uq_send_process_user_canal_recipient'
            );

            // ── Índices para consultas frecuentes ────────────────────
            // Vista "Mis Procesos": WHERE user_id = ? ORDER BY notified_at DESC
            $table->index(['user_id', 'notified_at'], 'idx_sends_user_notified');

            // Dedup en engine: WHERE user_id = ? AND canal = ? AND recipient_id = ?
            $table->index(['user_id', 'canal', 'recipient_id'], 'idx_sends_user_canal_recipient');

            // JOIN con notified_processes
            $table->index('notified_process_id', 'idx_sends_process');
        });

        // ── Permiso para ver procesos notificados ────────────────────
        $exists = DB::table('permissions')->where('slug', 'view-mis-procesos')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'name' => 'Ver Mis Procesos Notificados',
                'slug' => 'view-mis-procesos',
                'description' => 'Permite ver el historial de procesos que le fueron notificados al usuario',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Asignar a todos los roles existentes (funcionalidad básica de usuario)
            $permiso = DB::table('permissions')->where('slug', 'view-mis-procesos')->first();
            if ($permiso) {
                $roles = DB::table('roles')->pluck('id');
                foreach ($roles as $roleId) {
                    DB::table('permission_role')->insertOrIgnore([
                        'permission_id' => $permiso->id,
                        'role_id' => $roleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_sends');
        Schema::dropIfExists('notified_processes');

        DB::table('permissions')->where('slug', 'view-mis-procesos')->delete();
    }
};
