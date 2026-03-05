<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Jornadas de Trabalho</h2>
    </x-slot>
    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200 h-fit">
                <h3 class="text-lg font-bold mb-4">Nova Jornada</h3>
                <form action="{{ route('shifts.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <x-input-label value="Nome (Ex: Comercial 8h ou Plantão 12h)" />
                        <x-text-input name="name" class="w-full mt-1" required />
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div><x-input-label value="Entrada 1" /><x-text-input type="time" name="in_1" class="w-full mt-1" required /></div>
                        <div><x-input-label value="Saída 1" /><x-text-input type="time" name="out_1" class="w-full mt-1" required /></div>
                        <div><x-input-label value="Entrada 2" /><x-text-input type="time" name="in_2" class="w-full mt-1" /></div>
                        <div><x-input-label value="Saída 2" /><x-text-input type="time" name="out_2" class="w-full mt-1" /></div>
                    </div>

                    <div class="mb-4">
                        <x-input-label value="Tolerância de Atraso (Minutos)" />
                        <x-text-input type="number" name="tolerance_minutes" value="10" class="w-full mt-1" />
                    </div>

                    <div class="mb-5 bg-gray-50 p-3 rounded border border-gray-200">
                        <label class="inline-flex items-start cursor-pointer">
                            <input type="checkbox" name="is_12x36" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1">
                            <div class="ml-2">
                                <span class="text-sm text-gray-800 font-bold">Escala Rotativa (Ex: 12x36)</span>
                                <p class="text-xs text-gray-500 leading-tight mt-0.5">Ignora Feriados e Fins de Semana. Calcula "dia sim, dia não" baseado na data de início do servidor.</p>
                            </div>
                        </label>
                    </div>

                    <x-primary-button class="w-full justify-center">Salvar</x-primary-button>
                </form>
            </div>

            <div class="md:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4">Nome</th>
                            <th class="p-4">Horários</th>
                            <th class="p-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $shift)
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="p-4 font-medium">
                                {{ $shift->name }}
                                @if($shift->is_12x36)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800 uppercase tracking-wider border border-orange-200">
                                    Escala
                                </span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-600 font-mono text-xs">
                                {{ $shift->in_1 }} às {{ $shift->out_1 }}
                                @if($shift->in_2)
                                <br> {{ $shift->in_2 }} às {{ $shift->out_2 }}
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <form action="{{ route('shifts.destroy', $shift) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta jornada? Servidores vinculados a ela ficarão sem carga horária.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-medium hover:underline">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>