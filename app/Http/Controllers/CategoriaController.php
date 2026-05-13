<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Categoria::query();
        
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%');
        }
        
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        
        $categorias = $query->orderBy('nombre')->paginate(20);
        
        return response()->json($categorias);
    }

    public function select(Request $request)
    {
        $query = Categoria::query();
        
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        
        $categorias = $query->orderBy('nombre')->get(['id', 'nombre', 'tipo', 'color', 'icono']);
        
        return response()->json($categorias);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:200|unique:categorias,nombre',
            'tipo' => 'required|in:INGRESO,GASTO',
            'color' => 'nullable|string|size:7',
            'icono' => 'nullable|string|max:50'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $categoria = Categoria::create($request->only('nombre', 'tipo', 'color', 'icono'));
        
        return response()->json([
            'success' => true,
            'message' => 'Categoría creada correctamente',
            'data' => $categoria
        ]);
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:200|unique:categorias,nombre,' . $categoria->id,
            'tipo' => 'required|in:INGRESO,GASTO',
            'color' => 'nullable|string|size:7',
            'icono' => 'nullable|string|max:50'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $categoria->update($request->only('nombre', 'tipo', 'color', 'icono'));
        
        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente',
            'data' => $categoria
        ]);
    }

    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        
        if ($categoria->movimientos()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar, tiene movimientos asociados'
            ], 422);
        }
        
        $categoria->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada'
        ]);
    }
}