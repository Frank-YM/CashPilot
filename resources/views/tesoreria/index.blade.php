@extends('layouts.app')

@php
use App\Models\Movimiento;
@endphp

@section('content')
<style>
.module-hidden { display: none; }
.modal-backdrop { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.modal-box { background: white; border-radius: 0.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); max-width: 28rem; width: 100%; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
.modal-box.lg { max-width: 36rem; }
.modal-box.xl { max-width: 48rem; }
.toast { animation: slideIn 0.3s ease; }
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
.table-row:hover { background: #f9fafb; cursor: pointer; }
input:focus, select:focus, textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
.btn-primary { background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 500; transition: all 0.2s; }
.btn-primary:hover { background: #2563eb; }
.btn-danger { background: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 500; transition: all 0.2s; }
.btn-danger:hover { background: #dc2626; }
.badge-ingreso { background: #dcfce7; color: #166534; }
.badge-gasto { background: #fee2e2; color: #991b1b; }
.badge-transferencia { background: #e0e7ff; color: #3730a3; }
.badge-ajuste { background: #fef3c7; color: #92400e; }
.saldo-positivo { color: #16a34a; }
.saldo-negativo { color: #dc2626; }
</style>

<div>
    <!-- Dashboard Module -->
    <div id="mod-dashboard" class="module">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-5 text-white shadow-lg">
                <p class="text-sm opacity-80">Saldo Total</p>
                <p class="text-2xl font-bold">{{ number_format($totalGeneral, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Empresas</p>
                <p class="text-2xl font-bold">{{ count($saldosPorEmpresa) }}</p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Cuentas Activas</p>
                <p class="text-2xl font-bold">{{ count($saldosPorCuenta) }}</p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500">Transferencias</p>
                <p class="text-2xl font-bold">{{ $totalTransferencias }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b flex justify-between items-center">
                    <h3 class="font-semibold">Saldos por Empresa</h3>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($saldosPorEmpresa as $emp)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium">{{ $emp['nombre'] }}</p>
                            <p class="text-xs text-gray-400">ID: {{ $emp['id'] }}</p>
                        </div>
                        <span class="font-bold {{ $emp['saldo'] >= 0 ? 'saldo-positivo' : 'saldo-negativo' }}">
                            {{ number_format($emp['saldo'], 2) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-gray-400 text-center py-4">No hay empresas registradas</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b flex justify-between items-center">
                    <h3 class="font-semibold">Gastos por Categoría</h3>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($gastosPorCategoria as $cat)
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $cat->color }}"></div>
                            <span class="text-sm">{{ $cat->nombre }}</span>
                        </div>
                        <span class="font-medium text-red-600">{{ number_format($cat->movimientos_sum_monto, 2) }}</span>
                    </div>
                    @empty
                    <p class="text-gray-400 text-center py-4">Sin gastos registrados</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if($movimientosSinComprobante > 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 flex items-center gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <p class="font-medium text-yellow-800">Movimientos sin comprobante</p>
                <p class="text-sm text-yellow-700">Hay {{ $movimientosSinComprobante }} movimientos sin comprobante adjunto (más de 3 días).</p>
            </div>
        </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-semibold">Últimos Movimientos</h3>
                <button onclick="showModule('movimientos')" class="text-sm text-blue-600 hover:underline">Ver todos →</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-3 text-gray-500">Fecha</th>
                            <th class="text-left p-3 text-gray-500">Empresa</th>
                            <th class="text-left p-3 text-gray-500">Descripción</th>
                            <th class="text-left p-3 text-gray-500">Tipo</th>
                            <th class="text-right p-3 text-gray-500">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientosRecientes as $m)
                        <tr class="border-t">
                            <td class="p-3 text-gray-500">{{ $m->fecha->format('d/m/Y') }}</td>
                            <td class="p-3">{{ $m->empresa->nombre ?? '-' }}</td>
                            <td class="p-3">{{ Str::limit($m->descripcion, 30) ?: '-' }}</td>
                            <td class="p-3">
                                @php $badge = match(true) { $m->tipo == 'INGRESO' => 'badge-ingreso', $m->tipo == 'GASTO' => 'badge-gasto', $m->tipo == 'TRANSFERENCIA_ENTRADA' || $m->tipo == 'TRANSFERENCIA_SALIDA' => 'badge-transferencia', default => 'badge-ajuste' }; @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $badge }}">{{ str_replace('_', ' ', $m->tipo) }}</span>
                            </td>
                            <td class="p-3 text-right font-medium {{ Movimiento::esEntrada($m->tipo) ? 'saldo-positivo' : 'saldo-negativo' }}">
                                {{ Movimiento::esEntrada($m->tipo) ? '+' : '-' }}{{ number_format($m->monto, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="p-4 text-center text-gray-400">Sin movimientos recientes</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Empresas Module -->
    <div id="mod-empresas" class="module module-hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Empresas</h2>
            <button onclick="openEmpresaForm()" class="btn-primary">+ Nueva Empresa</button>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b">
                <input type="text" id="empresa-buscar" placeholder="Buscar empresa..." class="w-full max-w-xs px-3 py-2 border rounded-lg" oninput="loadEmpresas()">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-3 text-gray-500">Nombre</th>
                            <th class="text-left p-3 text-gray-500">RUC</th>
                            <th class="text-left p-3 text-gray-500">Teléfono</th>
                            <th class="text-left p-3 text-gray-500">Estado</th>
                            <th class="text-right p-3 text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="empresas-table-body"></tbody>
                </table>
            </div>
            <div id="empresas-pagination" class="p-4 border-t"></div>
        </div>
    </div>

    <!-- Cuentas Module -->
    <div id="mod-cuentas" class="module module-hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Cuentas</h2>
            <button onclick="openCuentaForm()" class="btn-primary">+ Nueva Cuenta</button>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b flex gap-4 flex-wrap">
                <input type="text" id="cuenta-buscar" placeholder="Buscar cuenta..." class="px-3 py-2 border rounded-lg" oninput="loadCuentas()">
                <select id="cuenta-empresa-filter" class="px-3 py-2 border rounded-lg" onchange="loadCuentas()">
                    <option value="">Todas las empresas</option>
                    @foreach($allEmpresas as $e)
                    <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-3 text-gray-500">Empresa</th>
                            <th class="text-left p-3 text-gray-500">Nombre</th>
                            <th class="text-left p-3 text-gray-500">Tipo</th>
                            <th class="text-left p-3 text-gray-500">Banco</th>
                            <th class="text-right p-3 text-gray-500">Saldo</th>
                            <th class="text-right p-3 text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuentas-table-body"></tbody>
                </table>
            </div>
            <div id="cuentas-pagination" class="p-4 border-t"></div>
        </div>
    </div>

    <!-- Categorías Module -->
    <div id="mod-categorias" class="module module-hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Categorías</h2>
            <button onclick="openCategoriaForm()" class="btn-primary">+ Nueva Categoría</button>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-3 text-gray-500">Nombre</th>
                            <th class="text-left p-3 text-gray-500">Tipo</th>
                            <th class="text-left p-3 text-gray-500">Color</th>
                            <th class="text-right p-3 text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="categorias-table-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Movimientos Module -->
    <div id="mod-movimientos" class="module module-hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Movimientos</h2>
            <button onclick="openMovimientoForm()" class="btn-primary">+ Nuevo Movimiento</button>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b flex gap-4 flex-wrap">
                <input type="text" id="mov-buscar" placeholder="Buscar..." class="px-3 py-2 border rounded-lg" oninput="loadMovimientos()">
                <select id="mov-empresa" class="px-3 py-2 border rounded-lg" onchange="loadCuentasForMov(); loadMovimientos()">
                    <option value="">Empresa</option>
                    @foreach($allEmpresas as $e)
                    <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                    @endforeach
                </select>
                <select id="mov-tipo" class="px-3 py-2 border rounded-lg" onchange="loadMovimientos()">
                    <option value="">Tipo</option>
                    <option value="INGRESO">Ingreso</option>
                    <option value="GASTO">Gasto</option>
                    <option value="TRANSFERENCIA_ENTRADA">Transferencia Entrada</option>
                    <option value="TRANSFERENCIA_SALIDA">Transferencia Salida</option>
                    <option value="AJUSTE_ENTRADA">Ajuste Entrada</option>
                    <option value="AJUSTE_SALIDA">Ajuste Salida</option>
                </select>
                <input type="date" id="mov-fecha-inicio" class="px-3 py-2 border rounded-lg" onchange="loadMovimientos()">
                <input type="date" id="mov-fecha-fin" class="px-3 py-2 border rounded-lg" onchange="loadMovimientos()">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-3 text-gray-500">Fecha</th>
                            <th class="text-left p-3 text-gray-500">Empresa</th>
                            <th class="text-left p-3 text-gray-500">Cuenta</th>
                            <th class="text-left p-3 text-gray-500">Categoría</th>
                            <th class="text-left p-3 text-gray-500">Tipo</th>
                            <th class="text-right p-3 text-gray-500">Monto</th>
                            <th class="text-right p-3 text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="movimientos-table-body"></tbody>
                </table>
            </div>
            <div id="movimientos-pagination" class="p-4 border-t"></div>
        </div>
    </div>

    <!-- Transferencias Module -->
    <div id="mod-transferencias" class="module module-hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Transferencias</h2>
            <button onclick="openTransferenciaForm()" class="btn-primary">+ Nueva Transferencia</button>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-3 text-gray-500">Fecha</th>
                            <th class="text-left p-3 text-gray-500">Origen</th>
                            <th class="text-left p-3 text-gray-500">Destino</th>
                            <th class="text-left p-3 text-gray-500">Referencia</th>
                            <th class="text-right p-3 text-gray-500">Monto</th>
                            <th class="text-right p-3 text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="transferencias-table-body"></tbody>
                </table>
            </div>
            <div id="transferencias-pagination" class="p-4 border-t"></div>
        </div>
    </div>
</div>

<div id="modal-container"></div>

<script>
const API = '/api';

// Toast
function showToast(message, type = 'success') {
    const c = document.getElementById('toast-container');
    const bg = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-blue-600' }[type] || 'bg-gray-600';
    const div = document.createElement('div');
    div.className = `toast ${bg} text-white px-4 py-3 rounded-lg shadow-lg`;
    div.textContent = message;
    c.appendChild(div);
    setTimeout(() => div.remove(), 4000);
}

// Module navigation
function showModule(name) {
    document.querySelectorAll('.module').forEach(m => m.classList.add('module-hidden'));
    document.getElementById('mod-' + name).classList.remove('module-hidden');
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-blue-600', 'text-white');
        b.classList.add('text-gray-600');
    });
    document.querySelector(`[data-tab="${name}"]`).classList.add('bg-blue-600', 'text-white');
    
    const loaders = { empresas: loadEmpresas, cuentas: loadCuentas, categorias: loadCategorias, movimientos: loadMovimientos, transferencias: loadTransferencias };
    if (loaders[name]) loaders[name](1);
}

// Modal
function openModal(title, content, size = 'md') {
    const w = { sm: 'max-w-md', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' }[size] || 'max-w-lg';
    const m = document.createElement('div');
    m.className = 'modal-backdrop';
    m.innerHTML = `<div class="modal-box ${w} w-full">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold">${title}</h3>
            <button onclick="this.closest('.modal-backdrop').remove()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <div class="p-4 overflow-y-auto" style="max-height: 70vh">${content}</div>
    </div>`;
    m.addEventListener('click', e => { if (e.target === m) m.remove(); });
    document.getElementById('modal-container').appendChild(m);
}

function closeModal() {
    document.querySelector('.modal-backdrop')?.remove();
}

// Helpers
function formatMoney(s) {
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(s);
}

async function apiFetch(url, opts = {}) {
    try {
        const r = await fetch(API + url, {
            headers: { 'Content-Type': 'application/json', ...opts.headers },
            ...opts
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.errors ? Object.values(d.errors).flat().join(', ') : (d.message || 'Error'));
        return d;
    } catch (e) {
        showToast(e.message, 'error');
        throw e;
    }
}

// =====================
// EMPRESAS
// =====================
async function loadEmpresas(page = 1) {
    const buscar = document.getElementById('empresa-buscar')?.value || '';
    const data = await apiFetch(`/empresas?page=${page}&buscar=${buscar}`);
    const tbody = document.getElementById('empresas-table-body');
    if (!data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-400">No hay empresas</td></tr>';
        return;
    }
    tbody.innerHTML = data.data.map(e => `
        <tr class="border-t">
            <td class="p-3 font-medium">${e.nombre}</td>
            <td class="p-3 text-gray-500">${e.ruc || '-'}</td>
            <td class="p-3 text-gray-500">${e.telefono || '-'}</td>
            <td class="p-3"><span class="px-2 py-1 text-xs rounded-full ${e.estado ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">${e.estado ? 'Activo' : 'Inactivo'}</span></td>
            <td class="p-3 text-right">
                <button onclick='editEmpresa(${JSON.stringify(e)})' class="text-blue-600 hover:underline mr-2">Editar</button>
                <button onclick='confirmDeleteEmpresa(${e.id})' class="text-red-600 hover:underline">Eliminar</button>
            </td>
        </tr>
    `).join('');
    renderPagination('empresas-pagination', data, loadEmpresas);
}

function openEmpresaForm(data = null) {
    const isEdit = !!data;
    openModal(isEdit ? 'Editar Empresa' : 'Nueva Empresa', `
        <form onsubmit="saveEmpresa(event, ${isEdit ? data.id : 'null'})">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="emp-nombre" value="${data?.nombre || ''}" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">RUC</label>
                    <input type="text" id="emp-ruc" value="${data?.ruc || ''}" maxlength="11" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" id="emp-telefono" value="${data?.telefono || ''}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="emp-estado" ${(data?.estado ?? true) ? 'checked' : ''} class="rounded">
                    <label for="emp-estado" class="text-sm">Activo</label>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar</button>
            </div>
        </form>
    `, 'sm');
}

async function saveEmpresa(e, id) {
    e.preventDefault();
    const payload = {
        nombre: document.getElementById('emp-nombre').value,
        ruc: document.getElementById('emp-ruc').value || null,
        telefono: document.getElementById('emp-telefono').value || null,
        estado: document.getElementById('emp-estado').checked
    };
    const data = id ? await apiFetch('/empresas/' + id, { method: 'PUT', body: JSON.stringify(payload) }) : await apiFetch('/empresas', { method: 'POST', body: JSON.stringify(payload) });
    showToast(data.message);
    closeModal();
    loadEmpresas();
}

function editEmpresa(e) { openEmpresaForm(e); }

async function confirmDeleteEmpresa(id) {
    if (!confirm('¿Eliminar esta empresa?')) return;
    const data = await apiFetch('/empresas/' + id, { method: 'DELETE' });
    showToast(data.message);
    loadEmpresas();
}

// =====================
// CUENTAS
// =====================
let allEmpresas = @json($allEmpresas);

async function loadCuentas(page = 1) {
    const buscar = document.getElementById('cuenta-buscar')?.value || '';
    const empresa_id = document.getElementById('cuenta-empresa-filter')?.value || '';
    const data = await apiFetch(`/cuentas?page=${page}&buscar=${buscar}&empresa_id=${empresa_id}`);
    const tbody = document.getElementById('cuentas-table-body');
    if (!data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-400">No hay cuentas</td></tr>';
        return;
    }
    tbody.innerHTML = data.data.map(c => `
        <tr class="border-t">
            <td class="p-3 text-sm">${c.empresa?.nombre || '-'}</td>
            <td class="p-3 font-medium">${c.nombre}</td>
            <td class="p-3"><span class="px-2 py-1 text-xs rounded-full bg-gray-100">${c.tipo}</span></td>
            <td class="p-3 text-gray-500">${c.banco || '-'}</td>
            <td class="p-3 text-right font-medium ${c.saldo >= 0 ? 'saldo-positivo' : 'saldo-negativo'}">${formatMoney(c.saldo)}</td>
            <td class="p-3 text-right">
                <button onclick='editCuenta(${JSON.stringify(c)})' class="text-blue-600 hover:underline mr-2">Editar</button>
                <button onclick='confirmDeleteCuenta(${c.id})' class="text-red-600 hover:underline">Eliminar</button>
            </td>
        </tr>
    `).join('');
    renderPagination('cuentas-pagination', data, loadCuentas);
}

function openCuentaForm(data = null) {
    const isEdit = !!data;
    openModal(isEdit ? 'Editar Cuenta' : 'Nueva Cuenta', `
        <form onsubmit="saveCuenta(event, ${isEdit ? data.id : 'null'})">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Empresa *</label>
                    <select id="cue-empresa" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Seleccionar empresa</option>
                        ${allEmpresas.map(e => `<option value="${e.id}" ${data?.empresa_id == e.id ? 'selected' : ''}>${e.nombre}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="cue-nombre" value="${data?.nombre || ''}" required class="w-full px-3 py-2 border rounded-lg" placeholder="Ej: Caja Chica, BCP, Yape...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select id="cue-tipo" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="CAJA" ${data?.tipo == 'CAJA' ? 'selected' : ''}>Caja</option>
                        <option value="BANCO" ${data?.tipo == 'BANCO' ? 'selected' : ''}>Banco</option>
                        <option value="WALLET" ${data?.tipo == 'WALLET' ? 'selected' : ''}>Wallet (Yape, Plin)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                    <input type="text" id="cue-banco" value="${data?.banco || ''}" class="w-full px-3 py-2 border rounded-lg" placeholder="Ej: BCP, Interbank, BBVA">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">N° Cuenta</label>
                    <input type="text" id="cue-numero" value="${data?.numero_cuenta || ''}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="cue-estado" ${(data?.estado ?? true) ? 'checked' : ''} class="rounded">
                    <label for="cue-estado" class="text-sm">Activo</label>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar</button>
            </div>
        </form>
    `, 'sm');
}

async function saveCuenta(e, id) {
    e.preventDefault();
    const payload = {
        empresa_id: document.getElementById('cue-empresa').value,
        nombre: document.getElementById('cue-nombre').value,
        tipo: document.getElementById('cue-tipo').value,
        banco: document.getElementById('cue-banco').value || null,
        numero_cuenta: document.getElementById('cue-numero').value || null,
        estado: document.getElementById('cue-estado').checked
    };
    const data = id ? await apiFetch('/cuentas/' + id, { method: 'PUT', body: JSON.stringify(payload) }) : await apiFetch('/cuentas', { method: 'POST', body: JSON.stringify(payload) });
    showToast(data.message);
    closeModal();
    loadCuentas();
}

function editCuenta(c) { openCuentaForm(c); }

async function confirmDeleteCuenta(id) {
    if (!confirm('¿Eliminar esta cuenta?')) return;
    const data = await apiFetch('/cuentas/' + id, { method: 'DELETE' });
    showToast(data.message);
    loadCuentas();
}

// =====================
// CATEGORIAS
// =====================
let allCategorias = @json($allCategorias);

async function loadCategorias(page = 1) {
    const data = await apiFetch('/categorias?page=' + page);
    const tbody = document.getElementById('categorias-table-body');
    if (!data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-gray-400">No hay categorías</td></tr>';
        return;
    }
    tbody.innerHTML = data.data.map(c => `
        <tr class="border-t">
            <td class="p-3 font-medium">${c.nombre}</td>
            <td class="p-3"><span class="px-2 py-1 text-xs rounded-full ${c.tipo == 'INGRESO' ? 'badge-ingreso' : 'badge-gasto'}">${c.tipo}</span></td>
            <td class="p-3"><div class="w-6 h-6 rounded" style="background:${c.color}"></div></td>
            <td class="p-3 text-right">
                <button onclick='editCategoria(${JSON.stringify(c)})' class="text-blue-600 hover:underline mr-2">Editar</button>
                <button onclick='confirmDeleteCategoria(${c.id})' class="text-red-600 hover:underline">Eliminar</button>
            </td>
        </tr>
    `).join('');
    renderPagination('categorias-pagination', data, loadCategorias);
}

function openCategoriaForm(data = null) {
    const isEdit = !!data;
    openModal(isEdit ? 'Editar Categoría' : 'Nueva Categoría', `
        <form onsubmit="saveCategoria(event, ${isEdit ? data.id : 'null'})">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="cat-nombre" value="${data?.nombre || ''}" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select id="cat-tipo" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="INGRESO" ${data?.tipo == 'INGRESO' ? 'selected' : ''}>Ingreso</option>
                        <option value="GASTO" ${data?.tipo == 'GASTO' ? 'selected' : ''}>Gasto</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="color" id="cat-color" value="${data?.color || '#6B7280'}" class="w-full h-10 rounded cursor-pointer">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar</button>
            </div>
        </form>
    `, 'sm');
}

async function saveCategoria(e, id) {
    e.preventDefault();
    const payload = {
        nombre: document.getElementById('cat-nombre').value,
        tipo: document.getElementById('cat-tipo').value,
        color: document.getElementById('cat-color').value
    };
    const data = id ? await apiFetch('/categorias/' + id, { method: 'PUT', body: JSON.stringify(payload) }) : await apiFetch('/categorias', { method: 'POST', body: JSON.stringify(payload) });
    showToast(data.message);
    closeModal();
    loadCategorias();
}

function editCategoria(c) { openCategoriaForm(c); }

async function confirmDeleteCategoria(id) {
    if (!confirm('¿Eliminar esta categoría?')) return;
    const data = await apiFetch('/categorias/' + id, { method: 'DELETE' });
    showToast(data.message);
    loadCategorias();
}

// =====================
// MOVIMIENTOS
// =====================
let cuentasForMov = @json($allCuentas);

function loadCuentasForMov() {
    const empresaId = document.getElementById('mov-empresa')?.value;
    return cuentasForMov.filter(c => !empresaId || c.empresa_id == empresaId);
}

async function loadMovimientos(page = 1) {
    const params = new URLSearchParams({
        page, empresa_id: document.getElementById('mov-empresa')?.value || '',
        tipo: document.getElementById('mov-tipo')?.value || '',
        fecha_inicio: document.getElementById('mov-fecha-inicio')?.value || '',
        fecha_fin: document.getElementById('mov-fecha-fin')?.value || '',
        buscar: document.getElementById('mov-buscar')?.value || ''
    });
    const data = await apiFetch('/movimientos?' + params.toString());
    const tbody = document.getElementById('movimientos-table-body');
    if (!data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-gray-400">No hay movimientos</td></tr>';
        return;
    }
    tbody.innerHTML = data.data.map(m => {
        const badge = m.tipo === 'INGRESO' ? 'badge-ingreso' : m.tipo === 'GASTO' ? 'badge-gasto' : m.tipo.includes('TRANSFERENCIA') ? 'badge-transferencia' : 'badge-ajuste';
        const isEntrada = ['INGRESO', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_ENTRADA'].includes(m.tipo);
        return `<tr class="border-t">
            <td class="p-3 text-gray-500">${new Date(m.fecha).toLocaleDateString('es-PE')}</td>
            <td class="p-3 text-sm">${m.empresa?.nombre || '-'}</td>
            <td class="p-3 text-sm">${m.cuenta?.nombre || '-'}</td>
            <td class="p-3 text-sm">${m.categoria?.nombre || '-'}</td>
            <td class="p-3"><span class="px-2 py-1 text-xs rounded-full ${badge}">${m.tipo.replace(/_/g, ' ')}</span></td>
            <td class="p-3 text-right font-medium ${isEntrada ? 'saldo-positivo' : 'saldo-negativo'}">${isEntrada ? '+' : '-'}${formatMoney(m.monto)}</td>
            <td class="p-3 text-right">
                <button onclick='editMovimiento(${JSON.stringify(m)})' class="text-blue-600 hover:underline mr-2">Editar</button>
                <button onclick='confirmDeleteMovimiento(${m.id})' class="text-red-600 hover:underline">Eliminar</button>
            </td>
        </tr>`;
    }).join('');
    renderPagination('movimientos-pagination', data, loadMovimientos);
}

function openMovimientoForm(data = null) {
    const isEdit = !!data;
    const empresas = allEmpresas;
    const todasCategorias = allCategorias;
    const cuentasDisponibles = data ? cuentasForMov.filter(c => c.empresa_id == data.empresa_id) : [];
    
    // Get the naturaleza from existing data or null for new
    const naturalezaActual = data ? (data.tipo === 'INGRESO' || data.tipo === 'TRANSFERENCIA_ENTRADA' || data.tipo === 'AJUSTE_ENTRADA' ? 'INGRESO' : 'GASTO') : null;
    
    openModal(isEdit ? 'Editar Movimiento' : 'Nuevo Movimiento', `
        <form onsubmit="saveMovimiento(event, ${isEdit ? data.id : 'null'})">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Empresa *</label>
                        <select id="mov-empresa-form" required class="w-full px-3 py-2 border rounded-lg" onchange="onEmpresaChangeForMov()">
                            <option value="">Seleccionar</option>
                            ${empresas.map(e => `<option value="${e.id}" ${data?.empresa_id == e.id ? 'selected' : ''}>${e.nombre}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta *</label>
                        <select id="mov-cuenta" required class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Seleccionar</option>
                            ${cuentasDisponibles.map(c => `<option value="${c.id}" ${data?.cuenta_id == c.id ? 'selected' : ''}>${c.nombre}</option>`).join('')}
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Concepto / Categoría *</label>
                    <div id="categoria-cards-container" class="grid grid-cols-3 gap-2 mb-2 max-h-48 overflow-y-auto"></div>
                    <button type="button" onclick="openMiniCategoriaForm()" class="mt-2 w-full px-3 py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-blue-400 hover:text-blue-500 transition-colors">+ Nuevo concepto</button>
                    <p id="categoria-error" class="text-red-500 text-sm mt-1 hidden">Selecciona un concepto</p>
                </div>
                
                <div id="mini-categoria-form" class="hidden bg-gray-50 rounded-lg p-4 mb-4 border">
                    <h4 class="text-sm font-medium mb-3">Nuevo Concepto</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Nombre *</label>
                            <input type="text" id="mini-cat-nombre" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Ej: Combustible, Peaje, etc">
                            <p id="mini-cat-nombre-error" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Naturaleza *</label>
                            <div class="flex gap-2">
                                <label class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer hover:bg-gray-100">
                                    <input type="radio" name="mini-cat-naturaleza" value="GASTO" checked class="text-blue-600">
                                    <span class="text-sm">Salida (Gasto)</span>
                                </label>
                                <label class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer hover:bg-gray-100">
                                    <input type="radio" name="mini-cat-naturaleza" value="INGRESO" class="text-blue-600">
                                    <span class="text-sm">Entrada (Ingreso)</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Color</label>
                            <input type="color" id="mini-cat-color" value="#6B7280" class="w-full h-8 rounded cursor-pointer">
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button type="button" onclick="closeMiniCategoriaForm()" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-200 rounded-lg">Cancelar</button>
                            <button type="button" onclick="saveMiniCategoria(event)" id="btn-save-mini-cat" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">Guardar</button>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                        <input type="number" step="0.01" min="0.01" id="mov-monto" value="${data?.monto || ''}" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input type="date" id="mov-fecha" value="${data?.fecha || new Date().toISOString().split('T')[0]}" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea id="mov-descripcion" rows="2" class="w-full px-3 py-2 border rounded-lg" placeholder="Descripción opcional...">${data?.descripcion || ''}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                    <input type="text" id="mov-referencia" value="${data?.referencia || ''}" class="w-full px-3 py-2 border rounded-lg" placeholder="N° comprobante, Factura, etc">
                </div>
            </div>
            <input type="hidden" id="mov-categoria-seleccionada" value="${data?.categoria_id || ''}">
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button type="submit" id="btn-guardar-movimiento" class="btn-primary">Guardar</button>
            </div>
        </form>
    `, 'lg');
    
    renderCategoriaCards(naturalezaActual, data?.categoria_id);
}

function onEmpresaChangeForMov() {
    const empresaId = document.getElementById('mov-empresa-form').value;
    const cuentasFiltradas = cuentasForMov.filter(c => c.empresa_id == empresaId);
    document.getElementById('mov-cuenta').innerHTML = '<option value="">Seleccionar</option>' + cuentasFiltradas.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
}

function renderCategoriaCards(naturalezaFilter = null, selectedId = null) {
    const container = document.getElementById('categoria-cards-container');
    if (!container) return;
    
    let categorias = allCategorias;
    if (naturalezaFilter) {
        categorias = categorias.filter(c => c.tipo === naturalezaFilter);
    }
    
    container.innerHTML = categorias.map(c => `
        <div onclick="selectCategoria(${c.id}, '${c.tipo}')" 
             class="categoria-card p-3 rounded-lg border-2 cursor-pointer transition-all text-center ${selectedId == c.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'}"
             data-id="${c.id}" data-tipo="${c.tipo}">
            <div class="w-8 h-8 rounded-full mx-auto mb-1 flex items-center justify-center" style="background-color: ${c.color}">
                <span class="text-white text-sm">${c.icono || c.nombre.charAt(0).toUpperCase()}</span>
            </div>
            <p class="text-xs font-medium text-gray-700 truncate">${c.nombre}</p>
            <p class="text-xs ${c.tipo === 'INGRESO' ? 'text-green-600' : 'text-red-600'}">${c.tipo === 'INGRESO' ? 'Entrada' : 'Salida'}</p>
        </div>
    `).join('');
}

function selectCategoria(catId, catTipo) {
    document.querySelectorAll('.categoria-card').forEach(c => c.classList.remove('border-blue-500', 'bg-blue-50'));
    document.querySelector(`[data-id="${catId}"]`).classList.add('border-blue-500', 'bg-blue-50');
    document.getElementById('mov-categoria-seleccionada').value = catId;
    document.getElementById('categoria-error').classList.add('hidden');
}

function openMiniCategoriaForm() {
    document.getElementById('mini-categoria-form').classList.remove('hidden');
    document.getElementById('mini-cat-nombre').value = '';
    document.getElementById('mini-cat-nombre-error').classList.add('hidden');
    document.querySelector('input[name="mini-cat-naturaleza"][value="GASTO"]').checked = true;
    document.getElementById('mini-cat-color').value = '#6B7280';
}

function closeMiniCategoriaForm() {
    document.getElementById('mini-categoria-form').classList.add('hidden');
}

async function saveMiniCategoria(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-mini-cat');
    btn.disabled = true;
    btn.textContent = 'Guardando...';
    
    const nombre = document.getElementById('mini-cat-nombre').value.trim();
    const tipo = document.querySelector('input[name="mini-cat-naturaleza"]:checked').value;
    const color = document.getElementById('mini-cat-color').value;
    
    if (!nombre) {
        document.getElementById('mini-cat-nombre-error').textContent = 'El nombre es requerido';
        document.getElementById('mini-cat-nombre-error').classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Guardar';
        return;
    }
    
    try {
        const result = await apiFetch('/categorias', {
            method: 'POST',
            body: JSON.stringify({ nombre, tipo, color, icono: nombre.charAt(0).toUpperCase() })
        });
        
        // Add to allCategorias and re-render
        allCategorias.push(result.data);
        closeMiniCategoriaForm();
        
        // Re-render with current filter
        const currentFilter = document.querySelector('input[name="categoria-filter"]')?.value || null;
        renderCategoriaCards(currentFilter, result.data.id);
        
        // Auto-select the new category
        selectCategoria(result.data.id, result.data.tipo);
        showToast('Concepto creado y seleccionado');
    } catch (e) {
        document.getElementById('mini-cat-nombre-error').textContent = e.message;
        document.getElementById('mini-cat-nombre-error').classList.remove('hidden');
    }
    
    btn.disabled = false;
    btn.textContent = 'Guardar';
}

async function saveMovimiento(e, id) {
    e.preventDefault();
    
    const catId = document.getElementById('mov-categoria-seleccionada').value;
    if (!catId) {
        document.getElementById('categoria-error').classList.remove('hidden');
        return;
    }
    
    const catSelected = allCategorias.find(c => c.id == catId);
    if (!catSelected) return;
    
    const btn = document.getElementById('btn-guardar-movimiento');
    btn.disabled = true;
    btn.textContent = 'Guardando...';
    
    // Determinar tipo basado en la naturaleza de la categoria
    const tipo = catSelected.tipo; // INGRESO o GASTO
    
    const payload = {
        empresa_id: document.getElementById('mov-empresa-form').value,
        cuenta_id: document.getElementById('mov-cuenta').value,
        categoria_id: catId,
        tipo: tipo,
        monto: document.getElementById('mov-monto').value,
        fecha: document.getElementById('mov-fecha').value,
        descripcion: document.getElementById('mov-descripcion').value || null,
        referencia: document.getElementById('mov-referencia').value || null
    };
    
    try {
        const data = id ? await apiFetch('/movimientos/' + id, { method: 'PUT', body: JSON.stringify(payload) }) : await apiFetch('/movimientos', { method: 'POST', body: JSON.stringify(payload) });
        showToast(data.message);
        closeModal();
        loadMovimientos();
    } catch (e) {
        // Show error in form
        showToast(e.message, 'error');
    }
    
    btn.disabled = false;
    btn.textContent = 'Guardar';
}

function editMovimiento(m) { openMovimientoForm(m); }

async function confirmDeleteMovimiento(id) {
    if (!confirm('¿Eliminar este movimiento?')) return;
    const data = await apiFetch('/movimientos/' + id, { method: 'DELETE' });
    showToast(data.message);
    loadMovimientos();
}

// =====================
// TRANSFERENCIAS
// =====================
async function loadTransferencias(page = 1) {
    const data = await apiFetch('/transferencias?page=' + page);
    const tbody = document.getElementById('transferencias-table-body');
    if (!data.data?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-400">No hay transferencias</td></tr>';
        return;
    }
    tbody.innerHTML = data.data.map(t => `
        <tr class="border-t">
            <td class="p-3 text-gray-500">${new Date(t.fecha).toLocaleDateString('es-PE')}</td>
            <td class="p-3 text-sm">${t.cuenta_origen?.nombre || '-'} <span class="text-gray-400">(${t.empresa_origen?.nombre})</span></td>
            <td class="p-3 text-sm">${t.cuenta_destino?.nombre || '-'} <span class="text-gray-400">(${t.empresa_destino?.nombre})</span></td>
            <td class="p-3 text-xs text-gray-500 font-mono">${t.referencia}</td>
            <td class="p-3 text-right font-medium saldo-positivo">${formatMoney(t.monto)}</td>
            <td class="p-3 text-right">
                <button onclick='confirmDeleteTransferencia(${t.id})' class="text-red-600 hover:underline">Eliminar</button>
            </td>
        </tr>
    `).join('');
    renderPagination('transferencias-pagination', data, loadTransferencias);
}

function openTransferenciaForm() {
    const empresas = allEmpresas;
    const todasCuentas = cuentasForMov;
    
    openModal('Nueva Transferencia', `
        <form onsubmit="saveTransferencia(event)">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Empresa Origen *</label>
                        <select id="trf-empresa-origen" required class="w-full px-3 py-2 border rounded-lg" onchange="onEmpresaOrigenChange()">
                            <option value="">Seleccionar</option>
                            ${empresas.map(e => `<option value="${e.id}">${e.nombre}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Empresa Destino *</label>
                        <select id="trf-empresa-destino" required class="w-full px-3 py-2 border rounded-lg" onchange="onEmpresaDestinoChange()">
                            <option value="">Seleccionar</option>
                            ${empresas.map(e => `<option value="${e.id}">${e.nombre}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Origen *</label>
                        <select id="trf-cuenta-origen" required class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Seleccionar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Destino *</label>
                        <select id="trf-cuenta-destino" required class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Seleccionar</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" step="0.01" id="trf-monto" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                    <input type="date" id="trf-fecha" value="${new Date().toISOString().split('T')[0]}" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea id="trf-descripcion" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar Transferencia</button>
            </div>
        </form>
    `, 'lg');
}

function onEmpresaOrigenChange() {
    const empId = document.getElementById('trf-empresa-origen').value;
    const cuentas = cuentasForMov.filter(c => c.empresa_id == empId);
    document.getElementById('trf-cuenta-origen').innerHTML = '<option value="">Seleccionar</option>' + cuentas.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
}

function onEmpresaDestinoChange() {
    const empId = document.getElementById('trf-empresa-destino').value;
    const cuentas = cuentasForMov.filter(c => c.empresa_id == empId);
    document.getElementById('trf-cuenta-destino').innerHTML = '<option value="">Seleccionar</option>' + cuentas.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
}

async function saveTransferencia(e) {
    e.preventDefault();
    const payload = {
        empresa_origen_id: document.getElementById('trf-empresa-origen').value,
        empresa_destino_id: document.getElementById('trf-empresa-destino').value,
        cuenta_origen_id: document.getElementById('trf-cuenta-origen').value,
        cuenta_destino_id: document.getElementById('trf-cuenta-destino').value,
        monto: document.getElementById('trf-monto').value,
        fecha: document.getElementById('trf-fecha').value,
        descripcion: document.getElementById('trf-descripcion').value || null
    };
    
    if (payload.empresa_origen_id === payload.empresa_destino_id && payload.cuenta_origen_id === payload.cuenta_destino_id) {
        showToast('No se puede transferir a la misma cuenta', 'error');
        return;
    }
    
    const data = await apiFetch('/transferencias', { method: 'POST', body: JSON.stringify(payload) });
    showToast(data.message);
    closeModal();
    loadTransferencias();
}

async function confirmDeleteTransferencia(id) {
    if (!confirm('¿Eliminar esta transferencia?')) return;
    const data = await apiFetch('/transferencias/' + id, { method: 'DELETE' });
    showToast(data.message);
    loadTransferencias();
}

// Pagination helper
function renderPagination(containerId, data, callback) {
    const container = document.getElementById(containerId);
    if (!data.last_page || data.last_page <= 1) {
        container.innerHTML = '';
        return;
    }
    let html = '<div class="flex justify-center gap-2">';
    for (let i = 1; i <= data.last_page; i++) {
        html += `<button onclick="${callback.name}(${i})" class="px-3 py-1 rounded ${i === data.current_page ? 'bg-blue-600 text-white' : 'bg-gray-100'}">${i}</button>`;
    }
    html += '</div>';
    container.innerHTML = html;
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    loadEmpresas();
});
</script>
@endsection