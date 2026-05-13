<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained()->onDelete('cascade');
            $table->string('nombre_original');
            $table->string('nombre_archivo');
            $table->string('ruta');
            $table->string('tipo_mime', 100);
            $table->bigInteger('tamano');
            $table->timestamps();
            
            $table->index('movimiento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};