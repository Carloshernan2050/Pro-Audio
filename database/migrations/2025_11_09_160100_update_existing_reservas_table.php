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
        if (! Schema::hasTable('reservas')) {
            return;
        }

        Schema::table('reservas', function (Blueprint $table) {
            $this->addDescripcionEvento($table);
            $this->addColumnIfMissing($table, 'servicio', function ($table) {
                $table->string('servicio')->nullable()->after('personas_id');
            });
            $this->addColumnIfMissing($table, 'personas_id', function ($table) {
                $table->foreignId('personas_id')->nullable()->after('id')->constrained('personas');
            });
            $this->addColumnIfMissing($table, 'cantidad_total', function ($table) {
                $table->unsignedInteger('cantidad_total')->default(0)->after('descripcion_evento');
            });
            $this->addColumnIfMissing($table, 'estado', function ($table) {
                $table->string('estado', 20)->default('pendiente')->after('cantidad_total');
            });
            $this->addColumnIfMissing($table, 'meta', function ($table) {
                $table->json('meta')->nullable()->after('estado');
            });
            $this->addColumnIfMissing($table, 'created_at', function ($table) {
                $table->timestamps();
            });
        });
    }

    /**
     * Add descripcion_evento column if missing.
     */
    private function addDescripcionEvento(Blueprint $table): void
    {
        if (Schema::hasColumn('reservas', 'descripcion_evento')) {
            return;
        }

        $afterColumn = Schema::hasColumn('reservas', 'fecha_fin') ? 'fecha_fin' : null;
        if ($afterColumn) {
            $table->text('descripcion_evento')->nullable()->after($afterColumn);
        } else {
            $table->text('descripcion_evento')->nullable();
        }
    }

    /**
     * Add a column if it doesn't exist.
     */
    private function addColumnIfMissing(Blueprint $table, string $columnName, callable $callback): void
    {
        if (! Schema::hasColumn('reservas', $columnName)) {
            $callback($table);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('reservas')) {
            return;
        }

        Schema::table('reservas', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'meta');
            $this->dropColumnIfExists($table, 'estado');
            $this->dropColumnIfExists($table, 'cantidad_total');
            $this->dropColumnIfExists($table, 'descripcion_evento');
            $this->dropPersonasIdIfExists($table);
            $this->dropTimestampsIfExists($table);
        });
    }

    /**
     * Drop a column if it exists.
     */
    private function dropColumnIfExists(Blueprint $table, string $columnName): void
    {
        if (Schema::hasColumn('reservas', $columnName)) {
            $table->dropColumn($columnName);
        }
    }

    /**
     * Drop personas_id column and foreign key if exists.
     */
    private function dropPersonasIdIfExists(Blueprint $table): void
    {
        if (Schema::hasColumn('reservas', 'personas_id')) {
            $table->dropForeign(['personas_id']);
            $table->dropColumn('personas_id');
        }
    }

    /**
     * Drop timestamps if both columns exist.
     */
    private function dropTimestampsIfExists(Blueprint $table): void
    {
        if (Schema::hasColumn('reservas', 'created_at') && Schema::hasColumn('reservas', 'updated_at')) {
            $table->dropTimestamps();
        }
    }
};
