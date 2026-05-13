<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transferencia extends Model
{
    protected $fillable = [
        'empresa_origen_id', 'empresa_destino_id', 'cuenta_origen_id',
        'cuenta_destino_id', 'monto', 'descripcion', 'fecha', 'referencia'
    ];

    protected $casts = ['monto' => 'decimal:2', 'fecha' => 'date'];

    public function empresaOrigen(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_origen_id');
    }

    public function empresaDestino(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_destino_id');
    }

    public function cuentaOrigen(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_origen_id');
    }

    public function cuentaDestino(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_destino_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'transferencia_id', 'referencia');
    }

    public static function generarReferencia(): string
    {
        return 'TRF-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
}