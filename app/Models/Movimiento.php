<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movimiento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'empresa_id', 'cuenta_id', 'categoria_id', 'tipo', 'monto',
        'descripcion', 'referencia', 'fecha', 'transferencia_id'
    ];

    protected $casts = ['monto' => 'decimal:2', 'fecha' => 'date'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class);
    }

    public static function esEntrada(string $tipo): bool
    {
        return in_array($tipo, ['INGRESO', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_ENTRADA']);
    }

    public static function esSalida(string $tipo): bool
    {
        return in_array($tipo, ['GASTO', 'TRANSFERENCIA_SALIDA', 'AJUSTE_SALIDA']);
    }

    public function getMontoConSignoAttribute(): float
    {
        return self::esEntrada($this->tipo) ? (float) $this->monto : -(float) $this->monto;
    }
}