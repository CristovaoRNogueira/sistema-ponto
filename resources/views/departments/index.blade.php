<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
            <h2 class="font-semibold text-xl text-gray-800">Organograma (Secretarias e Lotações)</h2>

            <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-department-exception')" type="button" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md shadow text-sm font-medium transition flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Recesso / Expediente Parcial
            </button>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">

        @if (session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm" role="alert">
            <span class="block sm:inline font-medium">{{ session('success') }}</span>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200 h-fit">
                <h3 class="text-lg font-bold mb-4">Nova Estrutura</h3>
                <form action="{{ route('departments.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <x-input-label value="Nome *" />
                        <x-text-input name="name" class="w-full mt-1" required placeholder="Ex: Secretaria de Saúde" />
                    </div>

                    <div class="mb-4">
                        <x-input-label value="Vincular à uma Secretaria (Opcional)" class="text-indigo-600 font-bold" />
                        <p class="text-xs text-gray-500 mb-1">Deixe em branco para criar uma nova Secretaria Maior.</p>
                        <select name="parent_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Nenhuma (É uma Secretaria Raiz)</option>
                            @foreach($secretariats as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-6">
                        <x-input-label value="Jornada de Trabalho Padrão (Opcional)" class="text-emerald-600 font-bold" />
                        <p class="text-xs text-gray-500 mb-1">Todos os servidores herdarão esta jornada automaticamente.</p>
                        <select name="shift_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Sem jornada definida (Herdará da Secretaria)</option>
                            @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-primary-button class="w-full justify-center">Salvar Estrutura</x-primary-button>
                </form>
            </div>

            <div class="md:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden p-6">
                <h3 class="text-lg font-bold mb-4">Estrutura Atual e Regras</h3>
                <ul class="space-y-4">
                    @foreach($secretariats as $sec)
                    <li class="border border-gray-200 rounded p-4 bg-gray-50 shadow-sm">
                        <div class="flex justify-between items-center mb-2">
                            <div>
                                <span class="font-black text-gray-800 text-lg uppercase">{{ $sec->name }}</span>
                                @if($sec->shift)
                                <span class="ml-2 px-2 py-0.5 text-xs bg-emerald-100 text-emerald-800 rounded font-bold border border-emerald-200" title="Jornada Padrão">
                                    ⏱️ {{ $sec->shift->name }}
                                </span>
                                @endif
                            </div>

                            <div class="flex items-center space-x-3">
                                <a href="{{ route('departments.edit', $sec) }}" class="text-indigo-600 text-sm hover:underline font-bold">Editar</a>
                                <form action="{{ route('departments.destroy', $sec) }}" method="POST" onsubmit="return confirm('Apagar Secretaria?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 text-sm hover:underline font-bold">Excluir</button>
                                </form>
                            </div>
                        </div>

                        @if($sec->children->count() > 0)
                        <ul class="mt-3 ml-4 space-y-2 border-l-2 border-indigo-200 pl-4">
                            @foreach($sec->children as $child)
                            <li class="flex justify-between items-center bg-white p-2 rounded border border-gray-100">
                                <div>
                                    <span class="text-gray-700 font-medium">↳ {{ $child->name }}</span>
                                    @if($child->shift)
                                    <span class="ml-2 px-2 py-0.5 text-[10px] bg-emerald-50 text-emerald-700 rounded border border-emerald-100" title="Jornada Padrão Específica">
                                        ⏱️ {{ $child->shift->name }}
                                    </span>
                                    @else
                                    <span class="ml-2 text-[10px] text-gray-400 italic">Herdando da Secretaria</span>
                                    @endif
                                </div>

                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('departments.edit', $child) }}" class="text-indigo-600 text-xs hover:underline">Editar</a>
                                    <form action="{{ route('departments.destroy', $child) }}" method="POST" onsubmit="return confirm('Apagar Departamento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 text-xs hover:underline">Excluir</button>
                                    </form>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <x-modal name="add-department-exception" focusable>
        <form method="POST" action="{{ route('departments.exceptions.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-bold text-gray-900 mb-2">Registrar Exceção / Recesso no Departamento</h2>
            <p class="text-xs text-gray-600 mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-2">
                Atenção: Esta regra recalculará a carga de <strong>TODOS</strong> os servidores vinculados a esta secretaria na data especificada, ignorando a jornada padrão.
            </p>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <x-input-label for="department_id" value="Departamento / Secretaria Afetada" />
                    <select id="department_id" name="department_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        <option value="">Selecione a Secretaria...</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="exception_date" value="Data Específica" />
                        <x-text-input id="exception_date" name="exception_date" type="date" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="type" value="Tipo de Funcionamento" />
                        <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required onchange="document.getElementById('partial_div').style.display = this.value === 'partial' ? 'block' : 'none'">
                            <option value="day_off">Recesso Total (Ponto Facultativo)</option>
                            <option value="partial">Expediente Parcial (Meio Período)</option>
                        </select>
                    </div>
                </div>

                <div id="partial_div" style="display: none;" class="bg-gray-50 p-4 rounded border border-gray-200 mt-2">
                    <x-input-label for="daily_work_minutes" value="Carga Horária Exigida (em Minutos)" />
                    <x-text-input id="daily_work_minutes" name="daily_work_minutes" type="number" class="mt-1 block w-full" placeholder="Ex: 240" />
                    <p class="text-[11px] text-gray-500 mt-1 font-bold">Dica: 4 horas = 240 minutos. O sistema cobrará apenas esse tempo dos servidores neste dia.</p>
                </div>

                <div>
                    <x-input-label for="observation" value="Motivo Oficial" />
                    <x-text-input id="observation" name="observation" type="text" class="mt-1 block w-full" placeholder="Ex: Decreto nº 123 - Véspera de Feriado" required />
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="bg-orange-600 hover:bg-orange-700">Aplicar Regra à Secretaria</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>