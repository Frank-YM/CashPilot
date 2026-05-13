<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CuentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Cuenta::with('empresa');
        
        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }
        
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%');
        }
        
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado === 'activo');
        }
        
        $cuentas = $query->orderBy('empresa_id')->orderBy('nombre')->paginate(15);
        
        return response()->json($cuentas);
    }

    public function select(Request $request)
    {
        $query = Cuenta::where('estado', true);
        
        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }
        
        $cuentas = $query->with('empresa:id,nombre')
            ->orderBy('empresa_id')
            ->orderBy('nombre')
            ->get(['id', 'empresa_id', 'nombre', 'tipo']);
        
        return response()->json($cuentas);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'nombre' => 'required|string|max:200',
            'tipo' => 'required|in:CAJA,BANCO,WALLET',
            'banco' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|max:50',
            'estado' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $cuenta = Cuenta::create($request->only('empresa_id', 'nombre', 'tipo', 'banco', 'numero_cuenta', 'estado'));
        $cuenta->load('empresa:id,nombre');
        
        return response()->json([
            'success' => true,
            'message' => 'Cuenta creada correctamente',
            'data' => $cuenta
        ]);
    }

    public function update(Request $request, $id)
    {
        $cuenta = Cuenta::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'nombre' => 'required|string|max:200',
            'tipo' => 'required|in:CAJA,BANCO,WALLET',
            'banco' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|max:50',
            'estado' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $cuenta->update($request->only('empresa_id', 'nombre', 'tipo', 'banco', 'numero_cuenta', 'estado'));
        $cuenta->load('empresa:id,nombre');
        
        return response()->json([
            'success' => true,
            'message' => 'Cuenta actualizada correctamente',
            'data' => $cuenta
        ]);
    }

    public function show($id)
    {
        $cuenta = Cuenta::with(['empresa', 'movimientos' => function ($q) {
            $q->orderBy('fecha', 'desc')->limit(50);
        }])->findOrFail($id);
        
        return response()->json($cuenta);
    }

    public function destroy($id)
    {
        $cuenta = Cuenta::findOrFail($id);
        
        if ($cuenta->movimientos()->exists()) {
            $cuenta->update(['estado' => false]);
            return response()->json([
                'success' => true,
                'message' => 'Cuenta desactivada'
            ]);
        }
        
        $cuenta->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada'
        ]);
    }
}