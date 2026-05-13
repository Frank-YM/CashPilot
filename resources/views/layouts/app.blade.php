<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tesorería - Sistema de Gestión</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .modal-backdrop { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
        .toast { animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .table-row:hover { background: #f9fafb; }
        input:focus, select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <h1 class="text-xl font-bold text-gray-800">💰 Tesorería</h1>
                <span class="text-sm text-gray-500">{{ date('d/m/Y') }}</span>
            </div>
        </div>
    </nav>

    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex gap-1 overflow-x-auto py-2" id="nav-tabs">
                <button onclick="showModule('dashboard')" data-tab="dashboard" class="tab-btn px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-blue-600 text-white">Dashboard</button>
                <button onclick="showModule('empresas')" data-tab="empresas" class="tab-btn px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Empresas</button>
                <button onclick="showModule('cuentas')" data-tab="cuentas" class="tab-btn px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Cuentas</button>
                <button onclick="showModule('categorias')" data-tab="categorias" class="tab-btn px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Categorías</button>
                <button onclick="showModule('movimientos')" data-tab="movimientos" class="tab-btn px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Movimientos</button>
                <button onclick="showModule('transferencias')" data-tab="transferencias" class="tab-btn px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100">Transferencias</button>
            </nav>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div id="main-content">
            @yield('content')
        </div>
    </main>

    <div id="modals-container"></div>

    @stack('scripts')
</body>
</html>