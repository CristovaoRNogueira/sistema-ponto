<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Agendamento de Férias</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">

        @if (session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm">
            <span class="block sm:inline font-bold">{{ session('success') }}</span>
        </div>
        @endif

        @if ($errors->any())
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                <li class="font-bold">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200 h-fit">
                <h3 class="text-lg font-bold mb-4 text-indigo-900 border-b pb-2">Agendar Período</h3>
                <form action="{{ route('vacations.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="employee_id" value="Selecione o Servidor *" />
                        <select name="employee_id" id="employee_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Buscar Servidor...</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} (Mat: {{ $emp->registration_number ?? 'S/N' }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <x-input-label for="start_date" value="Data de Início *" />
                            <x-text-input type="date" name="start_date" id="start_date" class="w-full mt-1 text-sm" value="{{ old('start_date') }}" required />
                        </div>
                        <div>
                            <x-input-label for="end_date" value="Data Final *" />
                            <x-text-input type="date" name="end_date" id="end_date" class="w-full mt-1 text-sm" value="{{ old('end_date') }}" required />
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 mb-4 bg-gray-50 p-2 rounded border border-gray-100">
                        O período selecionado <strong>não pode ultrapassar 30 dias</strong>.
                    </p>

                    <div class="mb-6">
                        <x-input-label for="observation" value="Observação / Número da Portaria (Opcional)" />
                        <x-text-input name="observation" id="observation" class="w-full mt-1 text-sm" value="{{ old('observation') }}" placeholder="Ex: Portaria nº 45/2026" />
                    </div>

                    <x-primary-button class="w-full justify-center bg-indigo-600 hover:bg-indigo-700 py-3">Salvar Férias</x-primary-button>
                </form>
            </div>

            <div class="md:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <div class="bg-indigo-50 px-4 py-3 border-b border-indigo-100">
                    <h3 class="font-bold text-indigo-800 uppercase text-sm">Férias Agendadas</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-2 text-gray-600">Servidor</th>
                                <th class="px-4 py-2 text-gray-600">Período de Férias</th>
                                <th class="px-4 py-2 text-center text-gray-600">Qtd. Dias</th>
                                <th class="px-4 py-2 text-center text-gray-600">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($vacations as $vacation)
                            @php
                            $s = \Carbon\Carbon::parse($vacation->start_date);
                            $e = \Carbon\Carbon::parse($vacation->end_date);
                            $dias = $s->diffInDays($e) + 1;
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-bold text-gray-900">
                                    {{ $vacation->employee->name }}
                                    <div class="text-[10px] text-gray-500 font-normal uppercase mt-0.5">{{ $vacation->employee->department->name ?? 'Sem Lotação' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 font-medium">
                                    {{ $s->format('d/m/Y') }} <span class="text-gray-400 font-normal mx-1">até</span> {{ $e->format('d/m/Y') }}
                                    @if($vacation->observation)
                                    <div class="text-xs text-gray-500 font-normal mt-1 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $vacation->observation }}
                                    </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-800 text-xs font-bold rounded-full">
                                        {{ $dias }} dias
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('vacations.destroy', $vacation->id) }}" onsubmit="return confirm('Excluir este agendamento de férias? O servidor voltará a ter faltas ou ser obrigado a bater o ponto.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-bold underline transition">
                                            Cancelar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Nenhuma férias cadastrada no sistema.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>