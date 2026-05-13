<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_origen_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('empresa_destino_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('cuenta_origen_id')->constrained('cuentas')->onDelete('cascade');
            $table->foreignId('cuenta_destino_id')->constrained('cuentas')->onDelete('cascade');
            $table->decimal('monto', 15, 2);
            $table->string('descripcion', 500)->nullable();
            $table->date('fecha');
            $table->string('referencia', 100)->unique();
            $table->timestamps();
            
            $table->index(['empresa_origen_id', 'fecha']);
            $table->index(['empresa_destino_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};