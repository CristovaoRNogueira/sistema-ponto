<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Organograma (Secretarias e Lotações)</h2></x-slot>
    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200 h-fit">
            <h3 class="text-lg font-bold mb-4">Nova Estrutura</h3>
            <form action="{{ route('departments.store') }}" method="POST">@csrf
                <div class="mb-4">
                    <x-input-label value="Nome *" />
                    <x-text-input name="name" class="w-full mt-1" required placeholder="Ex: Secretaria de Saúde"/>
                </div>
                
                <div class="mb-4">
                    <x-input-label value="Vincular à uma Secretaria (Opcional)" class="text-indigo-600 font-bold"/>
                    <p class="text-xs text-gray-500 mb-1">Deixe em branco para criar uma nova Secretaria Maior.</p>
                    <select name="parent_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Nenhuma (É uma Secretaria Raiz)</option>
                        @foreach($secretariats as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <x-input-label value="Jornada de Trabalho Padrão (Opcional)" class="text-emerald-600 font-bold"/>
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
    </div></div>
</x-app-layout>