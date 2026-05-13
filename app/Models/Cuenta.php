<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    use SoftDeletes;

    protected $fillable = ['empresa_id', 'nombre', 'tipo', 'banco', 'numero_cuenta', 'estado'];

    protected $casts = ['estado' => 'boolean'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class);
    }

    public function getSaldoAttribute(): float
    {
        $ingresos = $this->movimientos()
            ->whereIn('tipo', ['INGRESO', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_ENTRADA'])
            ->sum('monto');
        
        $egresos = $this->movimientos()
            ->whereIn('tipo', ['GASTO', 'TRANSFERENCIA_SALIDA', 'AJUSTE_SALIDA'])
            ->sum('monto');
        
        return round($ingresos - $egresos, 2);
    }

    public static function getSaldosPorCuenta(): array
    {
        return self::where('estado', true)
            ->with('empresa')
            ->get()
            ->mapWithKeys(fn($cuenta) => [
                $cuenta->id => [
                    'empresa' => $cuenta->empresa->nombre,
                    'nombre' => $cuenta->nombre,
                    'tipo' => $cuenta->tipo,
                    'saldo' => $cuenta->saldo
                ]
            ])->toArray();
    }
}