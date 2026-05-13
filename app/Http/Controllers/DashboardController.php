<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\Categoria;
use App\Models\Transferencia;

class DashboardController extends Controller
{
    public function index()
    {
        $empresas = Empresa::where('estado', true)->get();
        $saldosPorEmpresa = [];
        $totalGeneral = 0;
        
        foreach ($empresas as $empresa) {
            $saldo = $empresa->saldo;
            $saldosPorEmpresa[$empresa->id] = ['nombre' => $empresa->nombre, 'saldo' => $saldo, 'id' => $empresa->id];
            $totalGeneral += $saldo;
        }
        
        $saldosPorCuentaRaw = Cuenta::where('estado', true)->with('empresa:id,nombre')->get();
        $saldosPorCuenta = [];
        foreach ($saldosPorCuentaRaw as $cuenta) {
            $saldosPorCuenta[$cuenta->id] = [
                'empresa' => $cuenta->empresa->nombre,
                'nombre' => $cuenta->nombre,
                'tipo' => $cuenta->tipo,
                'saldo' => $cuenta->saldo
            ];
        }
        
        $movimientosRecientes = Movimiento::with(['empresa:id,nombre', 'cuenta:id,nombre', 'categoria:id,nombre,color'])
            ->orderBy('fecha', 'desc')
            ->limit(10)
            ->get();
        
        $gastosPorCategoria = Categoria::where('tipo', 'GASTO')
            ->withSum('movimientos', 'monto')
            ->get()
            ->filter(fn($c) => $c->movimientos_sum_monto > 0)
            ->sortByDesc('movimientos_sum_monto')
            ->take(7);
        
        $movimientosSinComprobante = Movimiento::doesntHave('comprobantes')
            ->whereIn('tipo', ['GASTO', 'INGRESO'])
            ->where('fecha', '<=', now()->subDays(3))
            ->count();
        
        $allEmpresas = Empresa::where('estado', true)->orderBy('nombre')->get(['id', 'nombre']);
        $allCuentas = Cuenta::where('estado', true)->with('empresa:id,nombre')->orderBy('nombre')->get(['id', 'empresa_id', 'nombre', 'tipo']);
        $allCategorias = Categoria::orderBy('nombre')->get(['id', 'nombre', 'tipo', 'color']);
        $totalTransferencias = Transferencia::count();
        
        return view('tesoreria.index', compact(
            'totalGeneral', 'saldosPorEmpresa', 'saldosPorCuenta',
            'movimientosRecientes', 'gastosPorCategoria', 'movimientosSinComprobante',
            'allEmpresas', 'allCuentas', 'allCategorias', 'totalTransferencias'
        ));
    }
}