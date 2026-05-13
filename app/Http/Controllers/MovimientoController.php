<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Models\Empresa;
use App\Models\Cuenta;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MovimientoController extends Controller
{
    public function index(Request $request)
    {
        $query = Movimiento::with(['empresa:id,nombre', 'cuenta:id,nombre', 'categoria:id,nombre,color']);
        
        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }
        if ($request->filled('cuenta_id')) {
            $query->where('cuenta_id', $request->cuenta_id);
        }
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('fecha_inicio')) {
            $query->where('fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->where('fecha', '<=', $request->fecha_fin);
        }
        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('descripcion', 'like', '%' . $request->buscar . '%')
                  ->orWhere('referencia', 'like', '%' . $request->buscar . '%');
            });
        }
        
        $movimientos = $query->orderBy('fecha', 'desc')->orderBy('id', 'desc')->paginate(20);
        
        return response()->json($movimientos);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'cuenta_id' => 'required|exists:cuentas,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'tipo' => 'required|in:INGRESO,GASTO,AJUSTE_ENTRADA,AJUSTE_SALIDA',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:500',
            'referencia' => 'nullable|string|max:100',
            'fecha' => 'required|date'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $cuenta = Cuenta::findOrFail($request->cuenta_id);
        if ($cuenta->empresa_id != $request->empresa_id) {
            return response()->json(['errors' => ['cuenta_id' => ['La cuenta no pertenece a la empresa seleccionada']]], 422);
        }
        
        $movimiento = DB::transaction(function () use ($request) {
            return Movimiento::create([
                'empresa_id' => $request->empresa_id,
                'cuenta_id' => $request->cuenta_id,
                'categoria_id' => $request->categoria_id,
                'tipo' => $request->tipo,
                'monto' => $request->monto,
                'descripcion' => $request->descripcion,
                'referencia' => $request->referencia ?: ('MV-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 4))),
                'fecha' => $request->fecha
            ]);
        });
        
        $movimiento->load(['empresa:id,nombre', 'cuenta:id,nombre', 'categoria:id,nombre,color']);
        
        return response()->json([
            'success' => true,
            'message' => 'Movimiento creado correctamente',
            'data' => $movimiento
        ]);
    }

    public function update(Request $request, $id)
    {
        $movimiento = Movimiento::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'cuenta_id' => 'required|exists:cuentas,id',
            'categoria_id' => 'nullable|exists:categorias,id',
            'tipo' => 'required|in:INGRESO,GASTO,AJUSTE_ENTRADA,AJUSTE_SALIDA',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:500',
            'referencia' => 'nullable|string|max:100',
            'fecha' => 'required|date'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $cuenta = Cuenta::findOrFail($request->cuenta_id);
        if ($cuenta->empresa_id != $request->empresa_id) {
            return response()->json(['errors' => ['cuenta_id' => ['La cuenta no pertenece a la empresa seleccionada']]], 422);
        }
        
        if ($movimiento->transferencia_id) {
            return response()->json(['errors' => ['general' => ['No se puede editar un movimiento de transferencia']]], 422);
        }
        
        $movimiento->update($request->only('empresa_id', 'cuenta_id', 'categoria_id', 'tipo', 'monto', 'descripcion', 'referencia', 'fecha'));
        $movimiento->load(['empresa:id,nombre', 'cuenta:id,nombre', 'categoria:id,nombre,color']);
        
        return response()->json([
            'success' => true,
            'message' => 'Movimiento actualizado correctamente',
            'data' => $movimiento
        ]);
    }

    public function show($id)
    {
        $movimiento = Movimiento::with(['empresa', 'cuenta', 'categoria', 'comprobantes'])->findOrFail($id);
        
        return response()->json($movimiento);
    }

    public function destroy($id)
    {
        $movimiento = Movimiento::findOrFail($id);
        
        if ($movimiento->transferencia_id) {
            $otros = Movimiento::where('transferencia_id', $movimiento->transferencia_id)
                ->where('id', '!=', $id)
                ->get();
            
            DB::transaction(function () use ($movimiento, $otros) {
                foreach ($otros as $otro) {
                    $otro->delete();
                }
                $movimiento->delete();
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Transferencia eliminada completamente'
            ]);
        }
        
        $movimiento->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Movimiento eliminado'
        ]);
    }

    public function ultimoSaldo(Request $request)
    {
        $request->validate([
            'cuenta_id' => 'required|exists:cuentas,id'
        ]);
        
        $cuenta = Cuenta::findOrFail($request->cuenta_id);
        
        return response()->json([
            'saldo_actual' => $cuenta->saldo,
            'cuenta' => ['id' => $cuenta->id, 'nombre' => $cuenta->nombre]
        ]);
    }
}