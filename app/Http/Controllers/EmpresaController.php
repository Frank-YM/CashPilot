<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $query = Empresa::query();
        
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%')
                  ->orWhere('ruc', 'like', '%' . $request->buscar . '%');
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado === 'activo');
        }
        
        $empresas = $query->orderBy('nombre')->paginate(15);
        
        return response()->json($empresas);
    }

    public function select(Request $request)
    {
        $empresas = Empresa::where('estado', true)->orderBy('nombre')->get(['id', 'nombre', 'ruc']);
        return response()->json($empresas);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:200|unique:empresas,nombre',
            'ruc' => 'nullable|string|size:11',
            'telefono' => 'nullable|string|max:20',
            'estado' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $empresa = Empresa::create($request->only('nombre', 'ruc', 'telefono', 'estado'));
        
        return response()->json([
            'success' => true,
            'message' => 'Empresa creada correctamente',
            'data' => $empresa
        ]);
    }

    public function update(Request $request, $id)
    {
        $empresa = Empresa::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:200', Rule::unique('empresas')->ignore($empresa->id)],
            'ruc' => 'nullable|string|size:11',
            'telefono' => 'nullable|string|max:20',
            'estado' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $empresa->update($request->only('nombre', 'ruc', 'telefono', 'estado'));
        
        return response()->json([
            'success' => true,
            'message' => 'Empresa actualizada correctamente',
            'data' => $empresa
        ]);
    }

    public function show($id)
    {
        $empresa = Empresa::with(['cuentas', 'movimientos' => function ($q) {
            $q->orderBy('fecha', 'desc')->limit(50);
        }])->findOrFail($id);
        
        return response()->json($empresa);
    }

    public function destroy($id)
    {
        $empresa = Empresa::findOrFail($id);
        
        if ($empresa->cuentas()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar, tiene cuentas asociadas'
            ], 422);
        }
        
        if ($empresa->movimientos()->exists()) {
            $empresa->update(['estado' => false]);
            return response()->json([
                'success' => true,
                'message' => 'Empresa desactivada'
            ]);
        }
        
        $empresa->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Empresa eliminada'
        ]);
    }
}