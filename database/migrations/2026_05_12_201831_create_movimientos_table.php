<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->foreignId('cuenta_id')->constrained()->onDelete('cascade');
            $table->foreignId('categoria_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('tipo', ['INGRESO', 'GASTO', 'TRANSFERENCIA_ENTRADA', 'TRANSFERENCIA_SALIDA', 'AJUSTE_ENTRADA', 'AJUSTE_SALIDA']);
            $table->decimal('monto', 15, 2);
            $table->string('descripcion', 500)->nullable();
            $table->string('referencia', 100)->nullable();
            $table->date('fecha');
            $table->string('transferencia_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['empresa_id', 'fecha']);
            $table->index(['cuenta_id', 'fecha']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};