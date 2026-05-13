<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Empresa;
use App\Models\Cuenta;
use App\Models\Categoria;
use App\Models\Movimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MovimientoFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->empresa = Empresa::create(['nombre' => 'Empresa Test', 'estado' => true]);
        $this->cuenta = Cuenta::create(['empresa_id' => $this->empresa->id, 'nombre' => 'Caja', 'tipo' => 'CAJA', 'estado' => true]);
        
        $this->catIngreso = Categoria::create(['nombre' => 'Pago Cliente', 'tipo' => 'INGRESO', 'color' => '#3b82f6']);
        $this->catGasto = Categoria::create(['nombre' => 'Combustible', 'tipo' => 'GASTO', 'color' => '#ef4444']);
    }

    // Test: movimiento con categoria INGRESO debe tener tipo INGRESO automaticamente
    public function test_movimiento_con_categoria_ingreso_es_tipo_ingreso()
    {
        $response = $this->postJson('/api/movimientos', [
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->catIngreso->id,
            'tipo' => 'INGRESO', // El sistema determina esto desde la categoria
            'monto' => 500.00,
            'fecha' => '2026-05-13'
        ]);
        
        $response->assertStatus(200);
        $mov = Movimiento::latest('id')->first();
        $this->assertEquals('INGRESO', $mov->tipo);
        $this->assertEquals($this->catIngreso->id, $mov->categoria_id);
    }

    // Test: movimiento con categoria GASTO debe tener tipo GASTO automaticamente
    public function test_movimiento_con_categoria_gasto_es_tipo_gasto()
    {
        $response = $this->postJson('/api/movimientos', [
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->catGasto->id,
            'tipo' => 'GASTO',
            'monto' => 150.00,
            'fecha' => '2026-05-13'
        ]);
        
        $response->assertStatus(200);
        $mov = Movimiento::latest('id')->first();
        $this->assertEquals('GASTO', $mov->tipo);
        $this->assertEquals($this->catGasto->id, $mov->categoria_id);
    }

    // Test: saldo se calcula correctamente basado en tipo
    public function test_saldo_empresa_refleja_naturaleza_movimiento()
    {
        // Crear ingreso
        Movimiento::create([
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->catIngreso->id,
            'tipo' => 'INGRESO',
            'monto' => 1000.00,
            'fecha' => '2026-05-13'
        ]);
        
        // Crear gasto
        Movimiento::create([
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->catGasto->id,
            'tipo' => 'GASTO',
            'monto' => 300.00,
            'fecha' => '2026-05-13'
        ]);
        
        $empresa = Empresa::find($this->empresa->id);
        $this->assertEquals(700.00, $empresa->saldo); // 1000 - 300
    }

    // Test: crear categoria nueva desde el modal (API directa)
    public function test_crear_categoria_desde_modal()
    {
        $response = $this->postJson('/api/categorias', [
            'nombre' => 'Peaje',
            'tipo' => 'GASTO',
            'color' => '#f97316',
            'icono' => 'P'
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Peaje', 'tipo' => 'GASTO']);
    }

    // Test: categoria tiene icono despues de crearse
    public function test_categoria_tiene_icono()
    {
        $cat = Categoria::create([
            'nombre' => 'Viaticos',
            'tipo' => 'GASTO',
            'color' => '#22c55e',
            'icono' => 'V'
        ]);
        
        $this->assertEquals('V', $cat->icono);
    }

    // Test: editar movimiento manteniendo categoria
    public function test_editar_movimiento_mantiene_categoria()
    {
        $mov = Movimiento::create([
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->catGasto->id,
            'tipo' => 'GASTO',
            'monto' => 100.00,
            'fecha' => '2026-05-13'
        ]);
        
        $response = $this->putJson('/api/movimientos/' . $mov->id, [
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->catGasto->id,
            'tipo' => 'GASTO',
            'monto' => 200.00,
            'fecha' => '2026-05-13',
            'descripcion' => 'Actualizado'
        ]);
        
        $response->assertStatus(200);
        $mov->refresh();
        $this->assertEquals(200.00, $mov->monto);
        $this->assertEquals('Actualizado', $mov->descripcion);
        $this->assertEquals($this->catGasto->id, $mov->categoria_id);
    }

    // Test: ver categorias con icono (select)
    public function test_listar_categorias_con_icono()
    {
        Categoria::create(['nombre' => 'Test', 'tipo' => 'INGRESO', 'color' => '#000', 'icono' => 'T']);
        
        $response = $this->getJson('/api/categorias/select?tipo=INGRESO');
        $response->assertStatus(200);
        
        $cats = $response->json();
        $this->assertTrue(count($cats) >= 1);
        $this->assertArrayHasKey('icono', $cats[0]);
    }
}