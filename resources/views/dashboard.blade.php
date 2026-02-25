<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center justify-between">
            <span>Visão Geral - {{ Auth::user()->company->name ?? 'RH' }}</span>
            <div class="text-sm font-normal text-gray-500 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Rankings atualizam a cada 60 min.
            </div>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-200">
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <x-input-label value="Secretaria / Lotação" />
                        <select name="department_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500">
                            <option value="">Todas as Lotações</option>
                            @foreach($secretariats as $sec)
                                <option value="{{ $sec->id }}" {{ $departmentId == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full md:w-1/6">
                        <x-input-label value="Mês" />
                        <select name="month" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach([1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'] as $num => $nome)
                                <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full md:w-1/6">
                        <x-input-label value="Ano" />
                        <input type="number" name="year" value="{{ $year }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex space-x-3">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md shadow text-sm font-medium transition">Filtrar</button>
                        
                        <button type="submit" formaction="{{ route('admin.export.monthly') }}" formtarget="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md shadow text-sm font-medium transition flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Exportar Fechamento (CSV)
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-500 font-medium">Servidores Ativos</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalEmployees ?? 0 }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 border-l-4 border-red-500">
                    <p class="text-sm text-gray-500 font-medium">Top Faltosos</p>
                    <p class="text-2xl font-bold text-gray-800">{{ count($rankings['absences']) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase">Servidores por Setor</h3>
                    <div class="relative h-48 w-full flex justify-center">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6 lg:col-span-2">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase">Feed de Batidas em Tempo Real</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-4 py-2 text-gray-700 font-bold">Data/Hora</th>
                                    <th class="px-4 py-2 text-gray-700 font-bold">Servidor</th>
                                    <th class="px-4 py-2 text-gray-700 font-bold">Origem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($punches ?? [] as $punch)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2">{{ $punch->punch_time->format('d/m/Y H:i:s') }}</td>
                                        <td class="px-4 py-2 font-medium">{{ $punch->employee->name }}</td>
                                        <td class="px-4 py-2 text-xs">
                                            @if($punch->is_manual || empty($punch->device))
                                                <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800">Manual (RH)</span>
                                            @else
                                                <span class="text-gray-500">{{ $punch->device->name }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">Nenhuma batida registrada.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-4 border-b bg-orange-50">
                        <h3 class="font-bold text-orange-800 uppercase text-sm">Servidores com Mais Atrasos</h3>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white border-b">
                            <tr>
                                <th class="px-4 py-2 text-gray-600">Servidor</th>
                                <th class="px-4 py-2 text-center text-gray-600">Dias Atrasados</th>
                                <th class="px-4 py-2 text-center text-gray-600">Tempo Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($rankings['delays'] as $delay)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold">{{ $delay['employee']->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $delay['employee']->department->name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-bold text-orange-600">{{ $delay['qtd'] }} dias</span>
                                        <div class="text-[10px] text-gray-400">({{ $delay['percent'] }}% do mês)</div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-orange-700 font-bold">{{ $delay['formatted_time'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Nenhum atraso crítico no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-4 border-b bg-red-50">
                        <h3 class="font-bold text-red-800 uppercase text-sm">Faltas / Sem Registro de Ponto</h3>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white border-b">
                            <tr>
                                <th class="px-4 py-2 text-gray-600">Servidor</th>
                                <th class="px-4 py-2 text-center text-gray-600">Dias Sem Ponto</th>
                                <th class="px-4 py-2 text-center text-gray-600">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($rankings['absences'] as $absence)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold">{{ $absence['employee']->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $absence['employee']->department->name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $absence['critical'] ? 'bg-red-200 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $absence['days'] }} faltas
                                        </span>
                                        <div class="text-[10px] text-gray-400 mt-1">Última: {{ $absence['last'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('admin.timesheet.report', ['employee' => $absence['employee']->id, 'month' => $month, 'year' => $year]) }}" class="text-indigo-600 hover:underline text-xs" target="_blank">Ver Espelho</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Nenhum servidor com ausências registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 lg:col-span-2">
                    <div class="p-4 border-b bg-blue-50 flex justify-between items-center">
                        <h3 class="font-bold text-blue-800 uppercase text-sm">Alertas de Banco de Horas</h3>
                        <span class="text-xs text-blue-600">Destaca saldos muito altos (> 40h) ou muito negativos (< -20h)</span>
                    </div>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white border-b">
                            <tr>
                                <th class="px-4 py-2 text-gray-600">Servidor / Lotação</th>
                                <th class="px-4 py-2 text-right text-gray-600">Saldo Acumulado no Mês</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($rankings['bankHours'] as $bank)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold">{{ $bank['employee']->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $bank['employee']->department->name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-lg font-bold">
                                        @if($bank['critical_positive'])
                                            <span class="text-green-600" title="Alerta: Muitas horas extras">⚠️ {{ $bank['formatted'] }}</span>
                                        @elseif($bank['critical_negative'])
                                            <span class="text-red-600" title="Alerta: Muitas horas devidas">⚠️ {{ $bank['formatted'] }}</span>
                                        @elseif($bank['balance_min'] > 0)
                                            <span class="text-green-600">{{ $bank['formatted'] }}</span>
                                        @else
                                            <span class="text-red-600">{{ $bank['formatted'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-4 py-6 text-center text-gray-500">Nenhum saldo crítico no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('deptChart');
            if(ctx && {!! json_encode($chartLabels) !!}.length > 0) {
                new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode($chartLabels) !!},
                        datasets: [{
                            data: {!! json_encode($chartData) !!},
                            backgroundColor: ['#4F46E5', '#3B82F6', '#0EA5E9', '#06B6D4', '#14B8A6', '#10B981', '#22C55E', '#F59E0B'],
                            borderWidth: 0
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            }
        });
    </script>
</x-app-layout>