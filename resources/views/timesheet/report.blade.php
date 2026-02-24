<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Espelho de Ponto: {{ $employee->name }}
            </h2>
            
            <div class="flex space-x-2 print:hidden">
                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-manual-punch')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md shadow text-sm font-medium transition flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Batida Manual
                </button>
                
                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-absence')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md shadow text-sm font-medium transition flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Atestado
                </button>

                <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-md shadow text-sm font-medium flex items-center transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Imprimir / PDF
                </button>
            </div>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6 print:hidden">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm" role="alert">
                <span class="block sm:inline font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <div class="py-8 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-200 print:shadow-none print:border-b-2 print:border-gray-800 print:rounded-none">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 uppercase tracking-wider text-sm border-b pb-2">Dados do Servidor</h3>
                        <p class="text-sm text-gray-700 mb-1"><strong>Órgão/Empresa:</strong> {{ $employee->company->name ?? 'Prefeitura Municipal' }}</p>
                        <p class="text-sm text-gray-700 mb-1"><strong>PIS:</strong> {{ $employee->pis }}</p>
                        <p class="text-sm text-gray-700 mb-1"><strong>Departamento:</strong> {{ $employee->department->name ?? 'Não vinculado' }}</p>
                        <p class="text-sm text-gray-700 mb-1"><strong>Jornada Vinculada:</strong> {{ $employee->shift->name ?? 'NENHUMA JORNADA DEFINIDA' }}</p>
                        <p class="text-sm text-gray-700"><strong>Mês de Apuração:</strong> <span class="capitalize">{{ $period }}</span></p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-indigo-50 p-4 rounded border border-indigo-100 print:border-gray-300 print:bg-white">
                            <p class="text-xs text-indigo-600 uppercase font-bold print:text-gray-800">Horas Trabalhadas</p>
                            <p class="text-2xl font-bold text-indigo-900 print:text-black">{{ $totalsFormatted['worked'] ?? '00:00' }}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded border border-green-100 print:border-gray-300 print:bg-white">
                            <p class="text-xs text-green-600 uppercase font-bold print:text-gray-800">Saldo Extra Positivo</p>
                            <p class="text-2xl font-bold text-green-900 print:text-black">{{ $totalsFormatted['overtime'] ?? '00:00' }}</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded border border-red-100 col-span-2 print:border-gray-300 print:bg-white">
                            <p class="text-xs text-red-600 uppercase font-bold print:text-gray-800">Atrasos / Faltas (Negativo)</p>
                            <p class="text-2xl font-bold text-red-900 print:text-black">{{ $totalsFormatted['delay'] ?? '00:00' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden print:shadow-none print:border-none">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-100 border-b border-gray-300">
                            <tr>
                                <th class="px-4 py-3 font-bold text-gray-700">Data</th>
                                <th class="px-4 py-3 font-bold text-gray-700">Situação</th>
                                <th class="px-4 py-3 font-bold text-gray-700 text-center">Entrada 1</th>
                                <th class="px-4 py-3 font-bold text-gray-700 text-center">Saída 1</th>
                                <th class="px-4 py-3 font-bold text-gray-700 text-center">Entrada 2</th>
                                <th class="px-4 py-3 font-bold text-gray-700 text-center">Saída 2</th>
                                <th class="px-4 py-3 font-bold text-gray-700 text-center">Trabalhado</th>
                                <th class="px-4 py-3 font-bold text-gray-700 text-right">Saldo do Dia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($report as $day)
                                @php
                                    $rowClass = 'bg-white';
                                    if($day['status'] === 'divergent') {
                                        $rowClass = 'bg-red-50';
                                    } elseif($day['status'] === 'holiday') {
                                        $rowClass = 'bg-blue-50';
                                    } elseif($day['is_weekend']) {
                                        $rowClass = 'bg-gray-100';
                                    }
                                @endphp
                                
                                <tr class="hover:bg-indigo-50 {{ $rowClass }} print:bg-white print:text-black">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-800">{{ $day['date'] }}</div>
                                        <div class="text-xs text-gray-500 capitalize">{{ $day['day_name'] }}</div>
                                    </td>
                                    
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-xs uppercase tracking-wider">
                                            @if($day['status'] === 'normal') <span class="text-green-600">Normal</span>
                                            @elseif($day['status'] === 'overtime') <span class="text-green-600">Hora Extra</span>
                                            @elseif($day['status'] === 'delay') <span class="text-red-600">Atraso/Falta</span>
                                            @elseif($day['status'] === 'divergent') <span class="text-orange-600">Incompleto</span>
                                            @elseif($day['status'] === 'day_off') <span class="text-gray-500">Folga / DSR</span>
                                            @elseif($day['status'] === 'holiday') <span class="text-blue-600">Feriado</span>
                                            @elseif($day['status'] === 'justified') <span class="text-purple-600">Atestado/Licença</span>
                                            @endif
                                        </div>
                                        @if(!empty($day['observation']))
                                            <div class="text-[10px] text-gray-500 mt-1 uppercase">{{ $day['observation'] }}</div>
                                        @endif
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center font-mono">{{ $day['punches'][0] ?? '--:--' }}</td>
                                    <td class="px-4 py-3 text-center font-mono">{{ $day['punches'][1] ?? '--:--' }}</td>
                                    <td class="px-4 py-3 text-center font-mono">{{ $day['punches'][2] ?? '--:--' }}</td>
                                    <td class="px-4 py-3 text-center font-mono">{{ $day['punches'][3] ?? '--:--' }}</td>
                                    
                                    <td class="px-4 py-3 text-center font-bold text-gray-700">
                                        {{ $day['worked_formatted'] !== '00:00' ? $day['worked_formatted'] : '-' }}
                                    </td>
                                    
                                    <td class="px-4 py-3 text-right font-bold">
                                        @if(in_array($day['status'], ['normal', 'overtime', 'delay']))
                                            @if($day['balance_minutes'] > 0)
                                                <span class="text-green-600">+{{ $day['balance_formatted'] }}</span>
                                            @elseif($day['balance_minutes'] < 0)
                                                <span class="text-red-600">-{{ $day['balance_formatted'] }}</span>
                                            @else
                                                <span class="text-gray-400">00:00</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="hidden print:flex justify-between mt-16 pt-8 px-12">
                <div class="text-center w-64 border-t border-gray-800 pt-2">
                    <p class="text-sm font-bold">Assinatura do Servidor</p>
                </div>
                <div class="text-center w-64 border-t border-gray-800 pt-2">
                    <p class="text-sm font-bold">Responsável do RH</p>
                </div>
            </div>

        </div>
    </div>

    <x-modal name="add-manual-punch" focusable>
        <form method="POST" action="{{ route('timesheet.manual-punch', $employee->id) }}" class="p-6">
            @csrf
            <h2 class="text-lg font-bold text-gray-900 mb-2">
                Inserir Batida Manual
            </h2>
            <p class="text-xs text-gray-600 mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-2">
                <strong>Atenção Portaria 671:</strong> As batidas originais não podem ser apagadas. Esta inclusão será marcada no sistema para fins de auditoria.
            </p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="punch_date" value="Data da Batida" />
                    <x-text-input id="punch_date" name="punch_date" type="date" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="punch_time" value="Hora da Batida" />
                    <x-text-input id="punch_time" name="punch_time" type="time" class="mt-1 block w-full" required />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="justification" value="Justificativa (Motivo)" />
                <x-text-input id="justification" name="justification" type="text" class="mt-1 block w-full" placeholder="Ex: Esqueceu de bater, Trabalho externo..." required />
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>
                <x-primary-button class="bg-indigo-600 hover:bg-indigo-700">
                    Salvar Batida
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="add-absence" focusable>
        <form method="POST" action="{{ route('timesheet.absence', $employee->id) }}" class="p-6">
            @csrf
            <h2 class="text-lg font-bold text-gray-900 mb-2">
                Registrar Atestado ou Licença
            </h2>
            <p class="text-xs text-gray-600 mb-6 bg-blue-50 border-l-4 border-blue-400 p-2">
                Os dias cadastrados aqui terão a carga horária abonada automaticamente (não gerarão falta nem saldo negativo no espelho).
            </p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="start_date" value="Data Inicial" />
                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="end_date" value="Data Final" />
                    <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" required />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="reason" value="Motivo / CID" />
                <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" placeholder="Ex: Atestado Médico, Licença Paternidade..." required />
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>
                <x-primary-button class="bg-purple-600 hover:bg-purple-700">
                    Registrar Atestado
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <style>
        @media print {
            body { background-color: white !important; }
            nav { display: none !important; }
            .min-h-screen { background-color: white !important; }
            @page { margin: 1cm; }
        }
    </style>
</x-app-layout>