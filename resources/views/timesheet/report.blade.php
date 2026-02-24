<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Espelho de Ponto: {{ $employee->name }}
            </h2>
            <button onclick="window.print()" class="print:hidden bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-md shadow text-sm font-medium flex items-center transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir / PDF
            </button>
        </div>
    </x-slot>

    <div class="py-8 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-200 print:shadow-none print:border-b-2 print:border-gray-800 print:rounded-none">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 uppercase tracking-wider text-sm border-b pb-2">Dados do Servidor</h3>
                        <p class="text-sm text-gray-700 mb-1"><strong>Empresa:</strong> {{ $employee->company->name ?? 'Prefeitura Municipal' }}</p>
                        <p class="text-sm text-gray-700 mb-1"><strong>PIS:</strong> {{ $employee->pis }}</p>
                        <p class="text-sm text-gray-700 mb-1"><strong>Departamento:</strong> {{ $employee->department->name ?? 'Não vinculado' }}</p>
                        <p class="text-sm text-gray-700"><strong>Mês de Apuração:</strong> <span class="capitalize">{{ $period }}</span></p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-indigo-50 p-4 rounded border border-indigo-100 print:border-gray-300 print:bg-white">
                            <p class="text-xs text-indigo-600 uppercase font-bold print:text-gray-800">Horas Trabalhadas</p>
                            <p class="text-2xl font-bold text-indigo-900 print:text-black">{{ $totalsFormatted['worked'] }}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded border border-green-100 print:border-gray-300 print:bg-white">
                            <p class="text-xs text-green-600 uppercase font-bold print:text-gray-800">Saldo Extra Positivo</p>
                            <p class="text-2xl font-bold text-green-900 print:text-black">{{ $totalsFormatted['overtime'] }}</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded border border-red-100 col-span-2 print:border-gray-300 print:bg-white">
                            <p class="text-xs text-red-600 uppercase font-bold print:text-gray-800">Atrasos / Faltas (Negativo)</p>
                            <p class="text-2xl font-bold text-red-900 print:text-black">{{ $totalsFormatted['delay'] }}</p>
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
                                    // Cores baseadas no status do dia
                                    $rowClass = '';
                                    if($day['status'] === 'divergent') $rowClass = 'bg-red-50';
                                    elseif(in_array($day['status'], ['weekend', 'holiday'])) $rowClass = 'bg-gray-50 text-gray-500';
                                @endphp
                                
                                <tr class="hover:bg-indigo-50 {{ $rowClass }} print:bg-white print:text-black">
                                    <td class="px-4 py-2 font-medium">{{ $day['date'] }}</td>
                                    
                                    <td class="px-4 py-2 font-medium text-xs uppercase tracking-wider">
                                        @if($day['status'] === 'regular') <span class="text-green-600">Normal</span>
                                        @elseif($day['status'] === 'absence') <span class="text-red-600 font-bold">Falta</span>
                                        @elseif($day['status'] === 'divergent') <span class="text-orange-600 font-bold">Incompleto</span>
                                        @elseif($day['status'] === 'weekend') <span>Final de Semana</span>
                                        @elseif($day['status'] === 'holiday') <span class="text-blue-600">Feriado</span>
                                        @elseif($day['status'] === 'excused') <span class="text-purple-600">Atestado/Abono</span>
                                        @endif
                                    </td>
                                    
                                    <td class="px-4 py-2 text-center font-mono">{{ $day['punches'][0] ?? '-' }}</td>
                                    <td class="px-4 py-2 text-center font-mono">{{ $day['punches'][1] ?? '-' }}</td>
                                    <td class="px-4 py-2 text-center font-mono">{{ $day['punches'][2] ?? '-' }}</td>
                                    <td class="px-4 py-2 text-center font-mono">{{ $day['punches'][3] ?? '-' }}</td>
                                    
                                    <td class="px-4 py-2 text-center font-bold">{{ $day['worked_formatted'] }}</td>
                                    
                                    <td class="px-4 py-2 text-right font-bold">
                                        @if($day['status'] !== 'divergent' && !in_array($day['status'], ['weekend', 'holiday']))
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

    <style>
        @media print {
            body { background-color: white !important; }
            nav { display: none !important; }
            .min-h-screen { background-color: white !important; }
        }
    </style>
</x-app-layout>