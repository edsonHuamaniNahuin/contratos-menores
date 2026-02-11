<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand nro_contratacion to handle large public numbers (e.g., 2254325262)
        DB::statement('ALTER TABLE contratos MODIFY nro_contratacion BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Revert to standard INT if needed (may truncate on large values)
        DB::statement('ALTER TABLE contratos MODIFY nro_contratacion INT NULL');
    }
};
