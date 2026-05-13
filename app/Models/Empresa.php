<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use SoftDeletes;

    protected $fillable = ['nombre', 'ruc', 'telefono', 'estado'];

    protected $casts = ['estado' => 'boolean'];

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
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

    public static function getSaldosPorEmpresa(): array
    {
        $empresas = self::where('estado', true)->with('cuentas')->get();
        $result = [];
        
        foreach ($empresas as $empresa) {
            $result[$empresa->id] = [
                'nombre' => $empresa->nombre,
                'saldo' => $empresa->saldo,
                'cuentas' => []
            ];
            
            foreach ($empresa->cuentas as $cuenta) {
                $result[$empresa->id]['cuentas'][$cuenta->id] = [
                    'nombre' => $cuenta->nombre,
                    'saldo' => $cuenta->saldo
                ];
            }
        }
        
        return $result;
    }
}