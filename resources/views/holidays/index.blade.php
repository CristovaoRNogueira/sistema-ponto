<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
            <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            Calendário de Feriados
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200 h-fit">
                    <h3 class="text-lg font-bold mb-1 text-gray-800">Novo Feriado</h3>
                    <p class="text-xs text-gray-500 mb-6 border-b pb-3">Dias cadastrados aqui abonam as horas de todos os servidores automaticamente.</p>
                    
                    <form action="{{ route('holidays.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <x-input-label value="Nome do Feriado *" />
                            <x-text-input name="name" class="w-full mt-1" required placeholder="Ex: Proclamação da República..."/>
                        </div>
                        
                        <div class="mb-4">
                            <x-input-label value="Data *" />
                            <x-text-input name="date" type="date" class="w-full mt-1" required />
                        </div>

                        <div class="mb-6">
                            <x-input-label value="Abrangência *" class="mb-1"/>
                            <div class="flex items-center mb-2">
                                <input id="municipal" type="radio" value="municipal" name="type" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500" checked>
                                <label for="municipal" class="ml-2 text-sm font-medium text-gray-900">Municipal (Apenas esta Prefeitura)</label>
                            </div>
                            <div class="flex items-center">
                                <input id="national" type="radio" value="national" name="type" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500">
                                <label for="national" class="ml-2 text-sm font-medium text-gray-900">Nacional / Estadual</label>
                            </div>
                        </div>

                        <x-primary-button class="w-full justify-center">Registrar Feriado</x-primary-button>
                    </form>
                </div>

                <div class="md:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-800">Feriados Cadastrados</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Data</th>
                                    <th scope="col" class="px-6 py-3">Nome / Motivo</th>
                                    <th scope="col" class="px-6 py-3 text-center">Tipo</th>
                                    <th scope="col" class="px-6 py-3 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($holidays as $holiday)
                                    <tr class="bg-white hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 font-bold text-gray-900">
                                            {{ $holiday->date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-800">
                                            {{ $holiday->name }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if($holiday->company_id)
                                                <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded border border-indigo-200">Municipal</span>
                                            @else
                                                <span class="bg-emerald-100 text-emerald-800 text-xs font-medium px-2.5 py-0.5 rounded border border-emerald-200">Nacional</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <form action="{{ route('holidays.destroy', $holiday->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar este feriado? Os cálculos de ponto serão refeitos considerando dia normal.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 font-medium underline underline-offset-4">
                                                    Excluir
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <p>Nenhum feriado cadastrado ainda.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($holidays->hasPages())
                        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                            {{ $holidays->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>