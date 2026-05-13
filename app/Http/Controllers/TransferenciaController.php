<?php

namespace App\Http\Controllers;

use App\Models\Transferencia;
use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TransferenciaController extends Controller
{
    public function index(Request $request)
    {
        $query = Transferencia::with(['empresaOrigen:id,nombre', 'empresaDestino:id,nombre', 'cuentaOrigen:id,nombre', 'cuentaDestino:id,nombre']);
        
        if ($request->filled('empresa_origen_id')) {
            $query->where('empresa_origen_id', $request->empresa_origen_id);
        }
        if ($request->filled('empresa_destino_id')) {
            $query->where('empresa_destino_id', $request->empresa_destino_id);
        }
        if ($request->filled('fecha_inicio')) {
            $query->where('fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->where('fecha', '<=', $request->fecha_fin);
        }
        
        $transferencias = $query->orderBy('fecha', 'desc')->paginate(20);
        
        return response()->json($transferencias);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empresa_origen_id' => 'required|exists:empresas,id',
            'empresa_destino_id' => 'required|exists:empresas,id',
            'cuenta_origen_id' => 'required|exists:cuentas,id',
            'cuenta_destino_id' => 'required|exists:cuentas,id',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:500',
            'fecha' => 'required|date'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        if ($request->empresa_origen_id == $request->empresa_destino_id && 
            $request->cuenta_origen_id == $request->cuenta_destino_id) {
            return response()->json(['errors' => ['general' => ['No se puede transferir a la misma cuenta']]], 422);
        }
        
        $referencia = Transferencia::generarReferencia();
        
        $data = DB::transaction(function () use ($request, $referencia) {
            $transferencia = Transferencia::create([
                'empresa_origen_id' => $request->empresa_origen_id,
                'empresa_destino_id' => $request->empresa_destino_id,
                'cuenta_origen_id' => $request->cuenta_origen_id,
                'cuenta_destino_id' => $request->cuenta_destino_id,
                'monto' => $request->monto,
                'descripcion' => $request->descripcion,
                'fecha' => $request->fecha,
                'referencia' => $referencia
            ]);
            
            Movimiento::create([
                'empresa_id' => $request->empresa_origen_id,
                'cuenta_id' => $request->cuenta_origen_id,
                'tipo' => 'TRANSFERENCIA_SALIDA',
                'monto' => $request->monto,
                'descripcion' => $request->descripcion ?: 'Transferencia saliente',
                'referencia' => $referencia,
                'fecha' => $request->fecha,
                'transferencia_id' => $transferencia->id
            ]);
            
            Movimiento::create([
                'empresa_id' => $request->empresa_destino_id,
                'cuenta_id' => $request->cuenta_destino_id,
                'tipo' => 'TRANSFERENCIA_ENTRADA',
                'monto' => $request->monto,
                'descripcion' => $request->descripcion ?: 'Transferencia entrante',
                'referencia' => $referencia,
                'fecha' => $request->fecha,
                'transferencia_id' => $transferencia->id
            ]);
            
            return $transferencia;
        });
        
        $data->load(['empresaOrigen:id,nombre', 'empresaDestino:id,nombre', 'cuentaOrigen:id,nombre', 'cuentaDestino:id,nombre']);
        
        return response()->json([
            'success' => true,
            'message' => 'Transferencia creada correctamente',
            'data' => $data
        ]);
    }

    public function show($id)
    {
        $transferencia = Transferencia::with([
            'empresaOrigen', 'empresaDestino', 
            'cuentaOrigen', 'cuentaDestino',
            'movimientos'
        ])->findOrFail($id);
        
        return response()->json($transferencia);
    }

    public function destroy($id)
    {
        $transferencia = Transferencia::findOrFail($id);
        
        DB::transaction(function () use ($transferencia) {
            Movimiento::where('referencia', $transferencia->referencia)->forceDelete();
            $transferencia->forceDelete();
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Transferencia eliminada'
        ]);
    }
}