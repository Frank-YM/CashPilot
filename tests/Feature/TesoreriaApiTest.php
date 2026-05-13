<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Empresa;
use App\Models\Cuenta;
use App\Models\Categoria;
use App\Models\Movimiento;
use App\Models\Transferencia;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TesoreriaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->empresa1 = Empresa::create(['nombre' => 'Empresa Test 1', 'ruc' => '12345678901', 'telefono' => '999111222', 'estado' => true]);
        $this->empresa2 = Empresa::create(['nombre' => 'Empresa Test 2', 'ruc' => '10987654321', 'telefono' => '999333444', 'estado' => true]);
        
        $this->categoriaIngreso = Categoria::create(['nombre' => 'Pago Cliente', 'tipo' => 'INGRESO', 'color' => '#3b82f6']);
        $this->categoriaGasto = Categoria::create(['nombre' => 'Combustible', 'tipo' => 'GASTO', 'color' => '#ef4444']);
        
        $this->cuenta1 = Cuenta::create(['empresa_id' => $this->empresa1->id, 'nombre' => 'Caja Chica', 'tipo' => 'CAJA', 'estado' => true]);
        $this->cuenta2 = Cuenta::create(['empresa_id' => $this->empresa1->id, 'nombre' => 'BCP', 'tipo' => 'BANCO', 'banco' => 'BCP', 'numero_cuenta' => '123-456789', 'estado' => true]);
        $this->cuenta3 = Cuenta::create(['empresa_id' => $this->empresa2->id, 'nombre' => 'Yape', 'tipo' => 'WALLET', 'estado' => true]);
    }

    // ========== EMPRESAS ==========
    public function test_listar_empresas()
    {
        $response = $this->getJson('/api/empresas');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_crear_empresa()
    {
        $data = ['nombre' => 'Nueva Empresa SA', 'ruc' => '20601543210', 'telefono' => '988776655', 'estado' => true];
        $response = $this->postJson('/api/empresas', $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('empresas', ['nombre' => 'Nueva Empresa SA']);
    }

    public function test_validar_empresa_sin_nombre()
    {
        $response = $this->postJson('/api/empresas', ['nombre' => '']);
        $response->assertStatus(422);
    }

    public function test_editar_empresa()
    {
        $response = $this->putJson('/api/empresas/' . $this->empresa1->id, ['nombre' => 'Empresa Actualizada']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('empresas', ['id' => $this->empresa1->id, 'nombre' => 'Empresa Actualizada']);
    }

    // ========== CUENTAS ==========
    public function test_listar_cuentas()
    {
        $response = $this->getJson('/api/cuentas');
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_crear_cuenta()
    {
        $data = ['empresa_id' => $this->empresa1->id, 'nombre' => 'Interbank', 'tipo' => 'BANCO', 'banco' => 'Interbank', 'estado' => true];
        $response = $this->postJson('/api/cuentas', $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('cuentas', ['nombre' => 'Interbank']);
    }

    public function test_validar_cuenta_sin_empresa()
    {
        $response = $this->postJson('/api/cuentas', ['nombre' => 'Test', 'tipo' => 'CAJA']);
        $response->assertStatus(422);
    }

    // ========== CATEGORIAS ==========
    public function test_listar_categorias()
    {
        $response = $this->getJson('/api/categorias');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_crear_categoria()
    {
        $data = ['nombre' => 'Nuevo Gasto', 'tipo' => 'GASTO', 'color' => '#ff0000'];
        $response = $this->postJson('/api/categorias', $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Nuevo Gasto']);
    }

    // ========== MOVIMIENTOS ==========
    public function test_crear_movimiento_ingreso()
    {
        $data = [
            'empresa_id' => $this->empresa1->id,
            'cuenta_id' => $this->cuenta1->id,
            'categoria_id' => $this->categoriaIngreso->id,
            'tipo' => 'INGRESO',
            'monto' => 500.00,
            'descripcion' => 'Pago de cliente',
            'fecha' => '2026-05-12'
        ];
        $response = $this->postJson('/api/movimientos', $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('movimientos', ['monto' => 500.00]);
    }

    public function test_crear_movimiento_gasto()
    {
        $data = [
            'empresa_id' => $this->empresa1->id,
            'cuenta_id' => $this->cuenta1->id,
            'categoria_id' => $this->categoriaGasto->id,
            'tipo' => 'GASTO',
            'monto' => 150.00,
            'descripcion' => 'Combustible',
            'fecha' => '2026-05-12'
        ];
        $response = $this->postJson('/api/movimientos', $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('movimientos', ['monto' => 150.00, 'tipo' => 'GASTO']);
    }

    public function test_calculo_saldo_empresa()
    {
        Movimiento::create([
            'empresa_id' => $this->empresa1->id,
            'cuenta_id' => $this->cuenta1->id,
            'tipo' => 'INGRESO',
            'monto' => 1000.00,
            'fecha' => '2026-05-12'
        ]);
        Movimiento::create([
            'empresa_id' => $this->empresa1->id,
            'cuenta_id' => $this->cuenta1->id,
            'tipo' => 'GASTO',
            'monto' => 300.00,
            'fecha' => '2026-05-12'
        ]);
        
        $empresa = Empresa::find($this->empresa1->id);
        $this->assertEquals(700.00, $empresa->saldo);
    }

    public function test_calculo_saldo_cuenta()
    {
        Movimiento::create([
            'empresa_id' => $this->empresa1->id,
            'cuenta_id' => $this->cuenta1->id,
            'tipo' => 'INGRESO',
            'monto' => 800.00,
            'fecha' => '2026-05-12'
        ]);
        Movimiento::create([
            'empresa_id' => $this->empresa1->id,
            'cuenta_id' => $this->cuenta1->id,
            'tipo' => 'GASTO',
            'monto' => 200.00,
            'fecha' => '2026-05-12'
        ]);
        
        $cuenta = Cuenta::find($this->cuenta1->id);
        $this->assertEquals(600.00, $cuenta->saldo);
    }

    public function test_validar_movimiento_cuenta_distinta_empresa()
    {
        $data = [
            'empresa_id' => $this->empresa1->id,
            'cuenta_id' => $this->cuenta3->id, // belongs to empresa2
            'tipo' => 'INGRESO',
            'monto' => 100,
            'fecha' => '2026-05-12'
        ];
        $response = $this->postJson('/api/movimientos', $data);
        $response->assertStatus(422);
    }

    // ========== TRANSFERENCIAS ==========
    public function test_crear_transferencia()
    {
        $data = [
            'empresa_origen_id' => $this->empresa1->id,
            'empresa_destino_id' => $this->empresa2->id,
            'cuenta_origen_id' => $this->cuenta1->id,
            'cuenta_destino_id' => $this->cuenta3->id,
            'monto' => 500.00,
            'descripcion' => 'Transferencia entre empresas',
            'fecha' => '2026-05-12'
        ];
        $response = $this->postJson('/api/transferencias', $data);
        $response->assertStatus(200);
        
        // Verify two movements were created
        $this->assertDatabaseHas('movimientos', ['tipo' => 'TRANSFERENCIA_SALIDA', 'monto' => 500.00]);
        $this->assertDatabaseHas('movimientos', ['tipo' => 'TRANSFERENCIA_ENTRADA', 'monto' => 500.00]);
    }

    public function test_no_permitir_transferencia_misma_cuenta()
    {
        $data = [
            'empresa_origen_id' => $this->empresa1->id,
            'empresa_destino_id' => $this->empresa1->id,
            'cuenta_origen_id' => $this->cuenta1->id,
            'cuenta_destino_id' => $this->cuenta1->id,
            'monto' => 100,
            'fecha' => '2026-05-12'
        ];
        $response = $this->postJson('/api/transferencias', $data);
        $response->assertStatus(422);
    }

    public function test_eliminar_transferencia_elimina_movimientos()
    {
        $data = [
            'empresa_origen_id' => $this->empresa1->id,
            'empresa_destino_id' => $this->empresa2->id,
            'cuenta_origen_id' => $this->cuenta1->id,
            'cuenta_destino_id' => $this->cuenta3->id,
            'monto' => 500.00,
            'fecha' => '2026-05-12'
        ];
        $trfResponse = $this->postJson('/api/transferencias', $data);
        $transferenciaId = $trfResponse->json('data.id');
        
        $this->assertDatabaseHas('movimientos', ['tipo' => 'TRANSFERENCIA_SALIDA']);
        
        $this->deleteJson('/api/transferencias/' . $transferenciaId);
        
        $count = \App\Models\Movimiento::where('referencia', $trfResponse->json('data.referencia'))->count();
        $this->assertEquals(0, $count, 'Los movimientos de la transferencia deben ser eliminados');
    }
}