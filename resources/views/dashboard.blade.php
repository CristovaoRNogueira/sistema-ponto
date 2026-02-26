<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center justify-between">
            <span>Visão Geral - {{ Auth::user()->company->name ?? 'RH' }}</span>
            <div class="text-sm font-normal text-gray-500 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Rankings atualizam a cada 5 min.
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
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
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
                    <p class="text-sm text-gray-500 font-medium">Faltas Integrais</p>
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
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-center text-gray-500">Nenhuma batida registrada.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 flex flex-col" x-data="{ expanded: false }">
                    <div class="p-4 border-b bg-orange-50 flex justify-between items-center">
                        <h3 class="font-bold text-orange-800 uppercase text-sm">Atrasos e Saídas Antecipadas</h3>
                        <span class="text-xs font-bold text-orange-600 bg-orange-100 px-2 py-1 rounded-full">{{ count($rankings['delays']) }} total</span>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white border-b">
                                <tr>
                                    <th class="px-4 py-2 text-gray-600">Servidor</th>
                                    <th class="px-4 py-2 text-center text-gray-600">Dias com Atraso</th>
                                    <th class="px-4 py-2 text-center text-gray-600">Saldo Líquido (Mês)</th>
                                    <th class="px-4 py-2 text-center text-gray-600">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($rankings['delays'] as $index => $delay)
                                <tr class="hover:bg-gray-50 transition-all duration-200" x-show="expanded || {{ $index }} < 5" style="{{ $index >= 5 ? 'display: none;' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-bold">{{ $delay['employee']->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $delay['employee']->department->name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-bold text-orange-600">{{ $delay['qtd'] }} dias</span>
                                        <div class="text-[10px] text-gray-400 mt-0.5">Último: {{ $delay['last'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-lg font-bold">
                                        @if($delay['saldo_min'] > 0)
                                        <span class="text-green-600">{{ $delay['formatted_saldo'] }}</span>
                                        @elseif($delay['saldo_min'] < 0)
                                            <span class="text-red-600">{{ $delay['formatted_saldo'] }}</span>
                                            @else
                                            <span class="text-gray-400">00:00</span>
                                            @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('admin.timesheet.report', ['employee' => $delay['employee']->id, 'month' => $month, 'year' => $year]) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-bold" target="_blank">Espelho</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Nenhum atraso crítico no período.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(count($rankings['delays']) > 5)
                    <div class="bg-gray-50 border-t border-gray-200 p-2 text-center mt-auto">
                        <button type="button" @click="expanded = !expanded" class="text-sm font-bold text-indigo-600 hover:text-indigo-900 focus:outline-none transition-colors w-full py-1" x-text="expanded ? 'Recolher Lista ▲' : 'Ver todos os {{ count($rankings['delays']) }} servidores ▼'"></button>
                    </div>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 flex flex-col" x-data="{ expanded: false }">
                    <div class="p-4 border-b bg-red-50 flex justify-between items-center">
                        <h3 class="font-bold text-red-800 uppercase text-sm">Faltas Integrais (Sem Ponto)</h3>
                        <span class="text-xs font-bold text-red-600 bg-red-100 px-2 py-1 rounded-full">{{ count($rankings['absences']) }} total</span>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white border-b">
                                <tr>
                                    <th class="px-4 py-2 text-gray-600">Servidor</th>
                                    <th class="px-4 py-2 text-center text-gray-600">Dias de Falta</th>
                                    <th class="px-4 py-2 text-center text-gray-600">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($rankings['absences'] as $index => $absence)
                                <tr class="hover:bg-gray-50 transition-all duration-200" x-show="expanded || {{ $index }} < 5" style="{{ $index >= 5 ? 'display: none;' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-bold">{{ $absence['employee']->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $absence['employee']->department->name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if(isset($absence['never_clocked_in']) && $absence['never_clocked_in'])
                                        <span class="px-2 py-1 bg-red-600 text-white font-black text-[10px] rounded uppercase shadow-sm tracking-wide">
                                            Nenhum Registro
                                        </span>
                                        <div class="text-[10px] text-gray-500 mt-1">Ausente o mês todo</div>
                                        @else
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $absence['critical'] ? 'bg-red-200 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $absence['days'] }} dias
                                        </span>
                                        <div class="text-[10px] text-gray-400 mt-1">Última: {{ $absence['last'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('admin.timesheet.report', ['employee' => $absence['employee']->id, 'month' => $month, 'year' => $year]) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-bold" target="_blank">Espelho</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-gray-500">Nenhum servidor com ausências registradas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(count($rankings['absences']) > 5)
                    <div class="bg-gray-50 border-t border-gray-200 p-2 text-center mt-auto">
                        <button type="button" @click="expanded = !expanded" class="text-sm font-bold text-indigo-600 hover:text-indigo-900 focus:outline-none transition-colors w-full py-1" x-text="expanded ? 'Recolher Lista ▲' : 'Ver todos os {{ count($rankings['absences']) }} inadimplentes ▼'"></button>
                    </div>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 lg:col-span-2 flex flex-col" x-data="{ expanded: false }">
                    <div class="p-4 border-b bg-blue-50 flex flex-col md:flex-row md:justify-between md:items-center">
                        <h3 class="font-bold text-blue-800 uppercase text-sm">Auditoria de Saldo Líquido (Carga Exigida vs Entregue)</h3>
                        <span class="text-xs font-bold text-blue-600 bg-blue-100 px-2 py-1 rounded-full">{{ count($rankings['bankHours']) }} servidores analisados</span>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white border-b">
                                <tr>
                                    <th class="px-4 py-2 text-gray-600">Servidor / Lotação</th>
                                    <th class="px-4 py-2 text-center text-gray-600">Carga Exigida</th>
                                    <th class="px-4 py-2 text-center text-gray-600">Total Trabalhado</th>
                                    <th class="px-4 py-2 text-right text-gray-600">Saldo Líquido</th>
                                    <th class="px-4 py-2 text-center text-gray-600">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($rankings['bankHours'] as $index => $bank)
                                <tr class="hover:bg-gray-50 transition-all duration-200" x-show="expanded || {{ $index }} < 5" style="{{ $index >= 5 ? 'display: none;' : '' }}">
                                    <td class="px-4 py-3 flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-none">
                                        <div>
                                            <div class="font-bold">{{ $bank['employee']->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $bank['employee']->department->name ?? 'N/A' }}</div>
                                        </div>
                                        @if(isset($bank['critical_positive']) && $bank['critical_positive'])
                                        <span class="bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded border border-green-200 uppercase whitespace-nowrap mt-1 sm:mt-0">Excesso de Extras</span>
                                        @elseif(isset($bank['critical_negative']) && $bank['critical_negative'])
                                        <span class="bg-red-100 text-red-800 text-[10px] font-bold px-2 py-0.5 rounded border border-red-200 uppercase whitespace-nowrap mt-1 sm:mt-0">Devendo Horas</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center font-mono text-gray-600">{{ $bank['carga_formatted'] ?? '00:00' }}</td>
                                    <td class="px-4 py-3 text-center font-mono text-indigo-600 font-bold">{{ $bank['trabalhado_formatted'] ?? '00:00' }}</td>

                                    <td class="px-4 py-3 text-right font-mono text-lg font-bold">
                                        @if(isset($bank['balance_min']) && $bank['balance_min'] > 0)
                                        <span class="text-green-600">{{ $bank['saldo_formatted'] }}</span>
                                        @elseif(isset($bank['balance_min']) && $bank['balance_min'] < 0)
                                            <span class="text-red-600">{{ $bank['saldo_formatted'] }}</span>
                                            @else
                                            <span class="text-gray-400">00:00</span>
                                            @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('admin.timesheet.report', ['employee' => $bank['employee']->id, 'month' => $month, 'year' => $year]) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-bold" target="_blank">Espelho</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Nenhum saldo computado no período.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(count($rankings['bankHours']) > 5)
                    <div class="bg-gray-50 border-t border-gray-200 p-2 text-center mt-auto">
                        <button type="button" @click="expanded = !expanded" class="text-sm font-bold text-indigo-600 hover:text-indigo-900 focus:outline-none transition-colors w-full py-1" x-text="expanded ? 'Recolher Lista ▲' : 'Ver todos os {{ count($rankings['bankHours']) }} servidores ▼'"></button>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('deptChart');
            const chartLabels = @json($chartLabels ?? []);
            const chartData = @json($chartData ?? []);

            if (ctx && chartLabels.length > 0) {
                new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            data: chartData,
                            backgroundColor: ['#4F46E5', '#3B82F6', '#0EA5E9', '#06B6D4', '#14B8A6', '#10B981', '#22C55E', '#F59E0B'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>