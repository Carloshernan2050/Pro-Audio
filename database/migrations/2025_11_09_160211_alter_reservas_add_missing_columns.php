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
            $this->addColumnIfMissing($table, 'servicio', function ($table) {
                $table->string('servicio')->nullable()->after('id');
            });
            $this->addColumnIfMissing($table, 'personas_id', function ($table) {
                $table->foreignId('personas_id')->nullable()->after('id')->constrained('personas');
            });
            $this->addColumnIfMissing($table, 'fecha_inicio', function ($table) {
                $table->dateTime('fecha_inicio')->nullable()->after('servicio');
            });
            $this->addColumnIfMissing($table, 'fecha_fin', function ($table) {
                $table->dateTime('fecha_fin')->nullable()->after('fecha_inicio');
            });
            $this->addColumnIfMissing($table, 'descripcion_evento', function ($table) {
                $table->text('descripcion_evento')->nullable()->after('fecha_fin');
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
            $this->addColumnIfMissing($table, 'calendario_id', function ($table) {
                $table->foreignId('calendario_id')->nullable()->after('meta')->constrained('calendario');
            });
            $this->addColumnIfMissing($table, 'created_at', function ($table) {
                $table->timestamps();
            });
        });
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
            $this->dropCalendarioIdIfExists($table);
            $this->dropColumnIfExists($table, 'meta');
            $this->dropColumnIfExists($table, 'estado');
            $this->dropColumnIfExists($table, 'cantidad_total');
            $this->dropColumnIfExists($table, 'descripcion_evento');
            $this->dropColumnIfExists($table, 'fecha_fin');
            $this->dropColumnIfExists($table, 'fecha_inicio');
            $this->dropPersonasIdIfExists($table);
            $this->dropColumnIfExists($table, 'servicio');
            $this->dropTimestampsIfExists($table);
        });
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
     * Drop a column if it exists.
     */
    private function dropColumnIfExists(Blueprint $table, string $columnName): void
    {
        if (Schema::hasColumn('reservas', $columnName)) {
            $table->dropColumn($columnName);
        }
    }

    /**
     * Drop calendario_id column and foreign key if exists.
     */
    private function dropCalendarioIdIfExists(Blueprint $table): void
    {
        if (Schema::hasColumn('reservas', 'calendario_id')) {
            $table->dropForeign(['calendario_id']);
            $table->dropColumn('calendario_id');
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
