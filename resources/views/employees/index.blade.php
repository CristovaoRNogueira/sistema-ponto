<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestão de Servidores') }}
            </h2>
            
            <a href="{{ route('employees.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 transition ease-in-out duration-150">
                + Novo Servidor
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex flex-col md:flex-row gap-6">
                
                <div class="w-full md:w-1/4">
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-200 h-fit sticky top-6">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2 uppercase text-xs tracking-wider">
                            Estrutura Organizacional
                        </h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li>
                                <a href="{{ route('employees.index') }}" class="flex items-center p-2 rounded-md transition-colors {{ !request('department_id') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'hover:bg-gray-50' }}">
                                    <span>👥 Todos os Servidores</span>
                                </a>
                            </li>
                            
                            @foreach($secretariats as $sec)
                                <li class="pt-2">
                                    <a href="{{ route('employees.index', ['department_id' => $sec->id]) }}" class="flex items-center p-2 rounded-md transition-colors {{ request('department_id') == $sec->id ? 'bg-indigo-50 text-indigo-700 font-bold' : 'hover:bg-gray-50 font-medium text-gray-800' }}">
                                        <span>🏢 {{ $sec->name }}</span>
                                    </a>
                                    
                                    @if($sec->children->count() > 0)
                                        <ul class="ml-4 mt-1 space-y-1 border-l-2 border-indigo-100 pl-3">
                                            @foreach($sec->children as $child)
                                                <li>
                                                    <a href="{{ route('employees.index', ['department_id' => $child->id]) }}" class="block p-1 rounded-md transition-colors {{ request('department_id') == $child->id ? 'text-indigo-700 font-bold' : 'hover:text-indigo-600' }}">
                                                        ↳ {{ $child->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="w-full md:w-3/4 space-y-4">
                    
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-200 flex justify-between items-center">
                        <form method="GET" action="{{ route('employees.index') }}" class="w-full flex space-x-2" id="search-form" onsubmit="event.preventDefault();">
                            @if(request('department_id'))
                                <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                            @endif

                            <div class="relative w-full">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" id="search-icon">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 hidden" id="loading-spinner">
                                    <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <input type="text" name="search" id="search-input" value="{{ request('search') }}" autocomplete="off"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-2.5" 
                                    placeholder="Comece a digitar o Nome, CPF ou PIS para buscar...">
                            </div>
                            
                            <a href="{{ route('employees.index', ['department_id' => request('department_id')]) }}" 
                               id="clear-button"
                               class="{{ request('search') ? 'flex' : 'hidden' }} items-center p-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg border border-gray-300 hover:bg-gray-200 transition-colors" title="Limpar busca">
                                Limpar
                            </a>
                        </form>
                    </div>

                    <form action="{{ route('employees.bulk_department') }}" method="POST" id="bulk-action-form" class="bg-indigo-50/50 border border-indigo-100 rounded-lg p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-sm">
                        @csrf
                        <div id="hidden-inputs-container"></div>
                        
                        <div class="text-sm text-indigo-800 font-medium flex items-center">
                            <span id="selected-count" class="font-black text-lg mr-1">0</span> servidores selecionados
                        </div>
                        <div class="flex items-center space-x-2 w-full md:w-auto">
                            <select name="target_department_id" required class="text-sm border-gray-300 rounded-md py-1.5 focus:ring-indigo-500 focus:border-indigo-500 w-full md:w-64">
                                <option value="">Mover selecionados para...</option>
                                @foreach($secretariats as $sec)
                                    <option value="{{ $sec->id }}" class="font-bold">{{ $sec->name }}</option>
                                    @foreach($sec->children as $child)
                                        <option value="{{ $child->id }}">&nbsp;&nbsp;&nbsp;↳ {{ $child->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            <button type="submit" id="btn-bulk-move" disabled class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                                Aplicar
                            </button>
                        </div>
                    </form>

                    <div id="table-container">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-600">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 w-4 text-center">
                                                <input type="checkbox" id="checkbox-all" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer">
                                            </th>
                                            <th scope="col" class="px-4 py-3">Nome / Departamento</th>
                                            <th scope="col" class="px-6 py-3 text-center">Documento</th>
                                            <th scope="col" class="px-6 py-3 text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($employees as $emp)
                                            <tr class="bg-white hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-4 text-center">
                                                    <input type="checkbox" value="{{ $emp->id }}" class="emp-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer">
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="font-bold text-gray-900">{{ $emp->name }}</div>
                                                    <div class="text-xs text-gray-500 mt-0.5">
                                                        {{ $emp->department->name ?? 'Sem Departamento' }}
                                                        @if($emp->department && $emp->department->parent)
                                                            <span class="text-gray-300 mx-1">|</span> {{ $emp->department->parent->name }}
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    @if($emp->cpf)
                                                        <span class="block font-mono text-xs text-gray-700">CPF: {{ preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", str_pad($emp->cpf, 11, '0', STR_PAD_LEFT)) }}</span>
                                                    @endif
                                                    @if($emp->pis)
                                                        <span class="block font-mono text-[10px] text-gray-400 mt-1">PIS: {{ $emp->pis }}</span>
                                                    @endif
                                                </td>
                                                
                                                <td class="px-6 py-4 flex justify-end items-center space-x-4">
                                                    <a href="{{ route('admin.timesheet.report', $emp->id) }}" target="_blank" class="text-green-600 hover:text-green-900 font-medium underline underline-offset-4">Espelho</a>
                                                    <a href="{{ route('employees.edit', $emp->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium underline underline-offset-4">Editar</a>
                                                    
                                                    <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('Excluir este servidor? O histórico de ponto não será apagado, mas o acesso será revogado.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 font-medium underline underline-offset-4">Excluir</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                                    Nenhum servidor encontrado.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            
                            @if($employees->hasPages())
                                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                                    {{ $employees->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('search-input');
            const searchForm = document.getElementById('search-form');
            const searchIcon = document.getElementById('search-icon');
            const loadingSpinner = document.getElementById('loading-spinner');
            const clearButton = document.getElementById('clear-button');
            const tableContainer = document.getElementById('table-container');
            const bulkForm = document.getElementById('bulk-action-form');
            const hiddenInputsContainer = document.getElementById('hidden-inputs-container');

            if (!searchInput || !tableContainer) return;

            let debounceTimer;

            // --- LÓGICA DA BUSCA EM TEMPO REAL ---
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                searchIcon.classList.add('hidden');
                loadingSpinner.classList.remove('hidden');

                if (this.value.trim().length > 0) {
                    clearButton.classList.remove('hidden');
                    clearButton.classList.add('flex');
                } else {
                    clearButton.classList.add('hidden');
                    clearButton.classList.remove('flex');
                }

                debounceTimer = setTimeout(() => {
                    const url = new URL(searchForm.action);
                    const formData = new FormData(searchForm);
                    
                    for (const [key, value] of formData.entries()) {
                        if (value) url.searchParams.append(key, value);
                    }

                    fetch(url)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newContent = doc.getElementById('table-container');
                            
                            if (newContent) {
                                tableContainer.innerHTML = newContent.innerHTML;
                            }
                            
                            loadingSpinner.classList.add('hidden');
                            searchIcon.classList.remove('hidden');
                            window.history.pushState({}, '', url);

                            // Reseta o contador de Lote ao pesquisar
                            updateBulkCounter();
                        });
                }, 400); 
            });

            // --- LÓGICA DO CONTADOR E CHECKBOXES ---
            function updateBulkCounter() {
                const count = document.querySelectorAll('.emp-checkbox:checked').length;
                const countSpan = document.getElementById('selected-count');
                const btnMove = document.getElementById('btn-bulk-move');
                
                if (countSpan) countSpan.innerText = count;
                if (btnMove) btnMove.disabled = (count === 0);
            }

            document.addEventListener('change', function(e) {
                if (e.target && e.target.id === 'checkbox-all') {
                    const isChecked = e.target.checked;
                    document.querySelectorAll('.emp-checkbox').forEach(cb => cb.checked = isChecked);
                    updateBulkCounter();
                }

                if (e.target && e.target.classList.contains('emp-checkbox')) {
                    updateBulkCounter();
                    if (!e.target.checked) {
                        const checkAll = document.getElementById('checkbox-all');
                        if (checkAll) checkAll.checked = false;
                    }
                }
            });

            // --- INTERCEPTA O FORMULÁRIO DE LOTE PARA INJETAR OS IDS ---
            if(bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    const checkedBoxes = document.querySelectorAll('.emp-checkbox:checked');
                    
                    if(checkedBoxes.length === 0) {
                        e.preventDefault();
                        alert('Selecione pelo menos um servidor na lista abaixo.');
                        return;
                    }

                    // Limpa os IDs antigos e injeta os novos como inputs ocultos no form
                    hiddenInputsContainer.innerHTML = '';
                    checkedBoxes.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'employee_ids[]';
                        input.value = cb.value;
                        hiddenInputsContainer.appendChild(input);
                    });
                    
                    // O form prossegue naturalmente fazendo o envio POST correto
                });
            }
        });
    </script>
</x-app-layout>