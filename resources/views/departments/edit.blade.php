<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('departments.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800">Editar Estrutura: {{ $department->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto sm:px-6 lg:px-8">
        
        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200">
            <form action="{{ route('departments.update', $department->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <x-input-label value="Nome *" />
                    <x-text-input name="name" class="w-full mt-1" value="{{ old('name', $department->name) }}" required />
                </div>
                
                <div class="mb-4">
                    <x-input-label value="Vincular a uma Secretaria (Opcional)" class="text-indigo-600 font-bold"/>
                    <p class="text-xs text-gray-500 mb-1">Selecione para transformá-lo num departamento filho.</p>
                    <select name="parent_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Nenhuma (Tornar uma Secretaria Raiz)</option>
                        @foreach($secretariats as $sec)
                            <option value="{{ $sec->id }}" {{ old('parent_id', $department->parent_id) == $sec->id ? 'selected' : '' }}>
                                {{ $sec->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-8">
                    <x-input-label value="Jornada de Trabalho Padrão (Opcional)" class="text-emerald-600 font-bold"/>
                    <p class="text-xs text-gray-500 mb-1">Todos os servidores desta lotação herdarão esta jornada automaticamente.</p>
                    <select name="shift_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Sem jornada definida (Herdará da Secretaria se houver)</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}" {{ old('shift_id', $department->shift_id) == $shift->id ? 'selected' : '' }}>
                                {{ $shift->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('departments.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none disabled:opacity-25 transition">
                        Cancelar
                    </a>
                    <x-primary-button>Atualizar Dados</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>