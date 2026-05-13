<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Empresa;
use App\Models\Cuenta;
use App\Models\Categoria;
use App\Models\Movimiento;
use App\Models\Transferencia;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CrudEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->empresa = Empresa::create(['nombre' => 'Empresa Test', 'ruc' => '12345678901', 'telefono' => '999111222', 'estado' => true]);
        $this->empresa2 = Empresa::create(['nombre' => 'Empresa Dos', 'ruc' => '10987654321', 'telefono' => '999333444', 'estado' => true]);
        
        $this->categoriaIngreso = Categoria::create(['nombre' => 'Pago Cliente', 'tipo' => 'INGRESO', 'color' => '#3b82f6']);
        $this->categoriaGasto = Categoria::create(['nombre' => 'Combustible', 'tipo' => 'GASTO', 'color' => '#ef4444']);
        
        $this->cuenta = Cuenta::create(['empresa_id' => $this->empresa->id, 'nombre' => 'Caja Chica', 'tipo' => 'CAJA', 'estado' => true]);
        $this->cuenta2 = Cuenta::create(['empresa_id' => $this->empresa2->id, 'nombre' => 'BCP', 'tipo' => 'BANCO', 'estado' => true]);
    }

    // ========== EDITAR EMPRESA ==========
    public function test_editar_empresa_cambia_nombre()
    {
        $response = $this->putJson('/api/empresas/' . $this->empresa->id, [
            'nombre' => 'Empresa Renombrada SA',
            'ruc' => '12345678901',
            'telefono' => '999111222',
            'estado' => true
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('empresas', ['id' => $this->empresa->id, 'nombre' => 'Empresa Renombrada SA']);
    }

    public function test_editar_empresa_cambia_estado_a_inactivo()
    {
        $response = $this->putJson('/api/empresas/' . $this->empresa->id, [
            'nombre' => 'Empresa Test',
            'ruc' => '12345678901',
            'telefono' => '999111222',
            'estado' => false
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('empresas', ['id' => $this->empresa->id, 'estado' => false]);
    }

    public function test_editar_empresa_rechaza_nombre_duplicado()
    {
        $response = $this->putJson('/api/empresas/' . $this->empresa->id, [
            'nombre' => 'Empresa Dos', // Already exists
            'ruc' => '12345678901',
            'telefono' => '999111222',
            'estado' => true
        ]);
        
        $response->assertStatus(422);
    }

    // ========== EDITAR CUENTA ==========
    public function test_editar_cuenta_cambia_nombre()
    {
        $response = $this->putJson('/api/cuentas/' . $this->cuenta->id, [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Caja Grande',
            'tipo' => 'CAJA',
            'estado' => true
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('cuentas', ['id' => $this->cuenta->id, 'nombre' => 'Caja Grande']);
    }

    public function test_editar_cuenta_cambia_de_empresa()
    {
        $response = $this->putJson('/api/cuentas/' . $this->cuenta->id, [
            'empresa_id' => $this->empresa2->id,
            'nombre' => 'Caja Chica',
            'tipo' => 'CAJA',
            'estado' => true
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('cuentas', ['id' => $this->cuenta->id, 'empresa_id' => $this->empresa2->id]);
    }

    public function test_editar_cuenta_inactiva()
    {
        $response = $this->putJson('/api/cuentas/' . $this->cuenta->id, [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Caja Chica',
            'tipo' => 'CAJA',
            'estado' => false
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('cuentas', ['id' => $this->cuenta->id, 'estado' => false]);
    }

    // ========== EDITAR CATEGORIA ==========
    public function test_editar_categoria_cambia_nombre()
    {
        $response = $this->putJson('/api/categorias/' . $this->categoriaGasto->id, [
            'nombre' => 'Combustible Diesel',
            'tipo' => 'GASTO',
            'color' => '#ff0000'
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('categorias', ['id' => $this->categoriaGasto->id, 'nombre' => 'Combustible Diesel']);
    }

    public function test_editar_categoria_cambia_color()
    {
        $response = $this->putJson('/api/categorias/' . $this->categoriaGasto->id, [
            'nombre' => 'Combustible',
            'tipo' => 'GASTO',
            'color' => '#ff8800'
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('categorias', ['id' => $this->categoriaGasto->id, 'color' => '#ff8800']);
    }

    // ========== EDITAR MOVIMIENTO ==========
    public function test_editar_movimiento_cambia_monto()
    {
        $mov = Movimiento::create([
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->categoriaGasto->id,
            'tipo' => 'GASTO',
            'monto' => 100.00,
            'descripcion' => 'Test',
            'fecha' => '2026-05-13'
        ]);
        
        $response = $this->putJson('/api/movimientos/' . $mov->id, [
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->categoriaGasto->id,
            'tipo' => 'GASTO',
            'monto' => 250.00,
            'descripcion' => 'Test Actualizado',
            'fecha' => '2026-05-13'
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('movimientos', ['id' => $mov->id, 'monto' => '250.00']);
    }

    public function test_editar_movimiento_no_permite_cuenta_otra_empresa()
    {
        $mov = Movimiento::create([
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'tipo' => 'GASTO',
            'monto' => 50.00,
            'fecha' => '2026-05-13'
        ]);
        
        $response = $this->putJson('/api/movimientos/' . $mov->id, [
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta2->id, // Different empresa
            'tipo' => 'GASTO',
            'monto' => 50.00,
            'fecha' => '2026-05-13'
        ]);
        
        $response->assertStatus(422);
    }

    // ========== VER DETALLES ==========
    public function test_ver_empresa_con_cuentas()
    {
        $response = $this->getJson('/api/empresas/' . $this->empresa->id);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('cuentas', $data);
        $this->assertEquals('Empresa Test', $data['nombre']);
    }

    public function test_ver_cuenta_con_empresa()
    {
        $response = $this->getJson('/api/cuentas/' . $this->cuenta->id);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Caja Chica', $data['nombre']);
        $this->assertArrayHasKey('empresa', $data);
    }

    public function test_ver_movimiento_con_relaciones()
    {
        $mov = Movimiento::create([
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->categoriaGasto->id,
            'tipo' => 'GASTO',
            'monto' => 75.00,
            'descripcion' => 'Gasto de prueba',
            'fecha' => '2026-05-13'
        ]);
        
        $response = $this->getJson('/api/movimientos/' . $mov->id);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(75.00, $data['monto']);
        $this->assertArrayHasKey('empresa', $data);
        $this->assertArrayHasKey('cuenta', $data);
        $this->assertArrayHasKey('categoria', $data);
    }

    // ========== ELIMINAR ==========
    public function test_eliminar_empresa_sin_cuentas()
    {
        $nueva = Empresa::create(['nombre' => 'Temporal', 'estado' => true]);
        $id = $nueva->id;
        $response = $this->deleteJson('/api/empresas/' . $id);
        $response->assertStatus(200);
        
        // With soft deletes, check via withTrashed
        $this->assertNull(Empresa::find($id));
        $this->assertNotNull(Empresa::withTrashed()->find($id));
    }

    public function test_eliminar_cuenta_sin_movimientos()
    {
        $nueva = Cuenta::create(['empresa_id' => $this->empresa->id, 'nombre' => 'Temporal', 'tipo' => 'CAJA', 'estado' => true]);
        $id = $nueva->id;
        $response = $this->deleteJson('/api/cuentas/' . $id);
        $response->assertStatus(200);
        
        $this->assertNull(Cuenta::find($id));
        $this->assertNotNull(Cuenta::withTrashed()->find($id));
    }

    public function test_eliminar_categoria_sin_movimientos()
    {
        $nueva = Categoria::create(['nombre' => 'Temporal', 'tipo' => 'GASTO', 'color' => '#000000']);
        $id = $nueva->id;
        $response = $this->deleteJson('/api/categorias/' . $id);
        $response->assertStatus(200);
        
        $this->assertNull(Categoria::find($id));
        $this->assertNotNull(Categoria::withTrashed()->find($id));
    }

    public function test_eliminar_categoria_con_movimientos_rechaza()
    {
        Movimiento::create([
            'empresa_id' => $this->empresa->id,
            'cuenta_id' => $this->cuenta->id,
            'categoria_id' => $this->categoriaGasto->id,
            'tipo' => 'GASTO',
            'monto' => 50.00,
            'fecha' => '2026-05-13'
        ]);
        
        $response = $this->deleteJson('/api/categorias/' . $this->categoriaGasto->id);
        $response->assertStatus(422);
    }
}