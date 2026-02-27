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
                        <select name="department_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 text-sm">
                            <option value="">Todas as Lotações</option>
                            @foreach($allDepartments as $dept)
                            <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                {{ $dept->parent ? $dept->parent->name . ' > ' : '' }}{{ $dept->name }}
                            </option>
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

                    @if(!empty($filterDate))
                    <input type="hidden" name="filter_date" value="{{ $filterDate }}">
                    @endif

                    <div class="flex space-x-3">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md shadow text-sm font-medium transition">Filtrar</button>

                        <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'operational-calendar')" type="button" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-md shadow-sm text-sm font-medium transition flex items-center justify-center" title="Ver Calendário de Dias Úteis">
                            <svg class="w-5 h-5 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Calendário
                        </button>

                        <button type="submit" formaction="{{ route('admin.export.monthly') }}" formtarget="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md shadow text-sm font-medium transition flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Exportar CSV
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
                    <p class="text-sm text-gray-500 font-medium">Faltas Integrais (Mês)</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $rankings['total_monthly_absences'] ?? count($rankings['absences']) }}</p>
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
                    <div class="p-4 border-b bg-orange-50 flex flex-wrap justify-between items-center gap-2">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-orange-800 uppercase text-sm">Atrasos e Saídas</h3>
                            <span class="text-xs font-bold text-orange-600 bg-orange-100 px-2 py-1 rounded-full hidden sm:inline-block">{{ count($rankings['delays']) }} listados</span>
                        </div>

                        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2 text-xs m-0">
                            <input type="hidden" name="department_id" value="{{ $departmentId ?? '' }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <span class="font-bold text-orange-700 hidden lg:inline">Dia exato:</span>
                            <input type="date" name="filter_date" value="{{ $filterDate ?? '' }}" onchange="this.form.submit()" class="text-xs border-orange-300 rounded shadow-sm py-1 focus:ring-orange-500 focus:border-orange-500 cursor-pointer" title="Verificar atrasos neste dia específico">
                            @if(!empty($filterDate))
                            <a href="{{ route('dashboard', ['department_id' => $departmentId, 'month' => $month, 'year' => $year]) }}" class="text-red-500 hover:text-red-700 font-bold ml-1 text-sm transition" title="Limpar Filtro de Data">✖</a>
                            @endif
                        </form>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white border-b">
                                <tr>
                                    <th class="px-4 py-2 text-gray-600">Servidor</th>
                                    <th class="px-4 py-2 text-center text-gray-600">{{ empty($filterDate) ? 'Dias com Atraso' : 'Atrasou no Dia?' }}</th>
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
                                        @if(empty($filterDate))
                                        <span class="font-bold text-orange-600">{{ $delay['qtd'] }} dias</span>
                                        <div class="text-[10px] text-gray-400 mt-0.5">Último: {{ $delay['last'] }}</div>
                                        @else
                                        <span class="font-bold text-orange-600 uppercase text-xs">Sim</span>
                                        <div class="text-[10px] text-gray-400 mt-0.5">Total Mês: {{ $delay['qtd'] }} dias</div>
                                        @endif
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
                    <div class="p-4 border-b bg-red-50 flex flex-wrap justify-between items-center gap-2">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-red-800 uppercase text-sm">Faltas Integrais</h3>
                            <span class="text-xs font-bold text-red-600 bg-red-100 px-2 py-1 rounded-full hidden sm:inline-block">{{ count($rankings['absences']) }} listados</span>
                        </div>

                        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2 text-xs m-0">
                            <input type="hidden" name="department_id" value="{{ $departmentId ?? '' }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <span class="font-bold text-red-700 hidden lg:inline">Dia exato:</span>
                            <input type="date" name="filter_date" value="{{ $filterDate ?? '' }}" onchange="this.form.submit()" class="text-xs border-red-300 rounded shadow-sm py-1 focus:ring-red-500 focus:border-red-500 cursor-pointer" title="Verificar faltas neste dia específico">
                            @if(!empty($filterDate))
                            <a href="{{ route('dashboard', ['department_id' => $departmentId, 'month' => $month, 'year' => $year]) }}" class="text-red-500 hover:text-red-700 font-bold ml-1 text-sm transition" title="Limpar Filtro de Data">✖</a>
                            @endif
                        </form>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white border-b">
                                <tr>
                                    <th class="px-4 py-2 text-gray-600">Servidor</th>
                                    <th class="px-4 py-2 text-center text-gray-600">{{ empty($filterDate) ? 'Dias de Falta' : 'Faltou no Dia?' }}</th>
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
                                        @if(empty($filterDate))
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $absence['critical'] ? 'bg-red-200 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $absence['days'] }} dias
                                        </span>
                                        <div class="text-[10px] text-gray-400 mt-1">Última: {{ $absence['last'] }}</div>
                                        @else
                                        <span class="font-bold text-red-600 uppercase text-xs">Sim</span>
                                        <div class="text-[10px] text-gray-400 mt-0.5">Total Mês: {{ $absence['days'] }} dias</div>
                                        @endif
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

    <x-modal name="operational-calendar" focusable>
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-gray-900">Calendário Operacional - {{ $month }}/{{ $year }}</h2>
                <button x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="flex flex-wrap gap-3 mb-4 text-xs">
                <div class="flex items-center"><span class="w-3 h-3 bg-white border border-gray-200 rounded mr-1"></span> Dia Útil</div>
                <div class="flex items-center"><span class="w-3 h-3 bg-gray-100 border border-gray-200 rounded mr-1"></span> Fim de Semana</div>
                <div class="flex items-center"><span class="w-3 h-3 bg-blue-100 border border-blue-200 rounded mr-1"></span> Feriado</div>
                <div class="flex items-center"><span class="w-3 h-3 bg-red-100 border border-red-200 rounded mr-1"></span> Recesso Depto</div>
                <div class="flex items-center"><span class="w-3 h-3 bg-orange-100 border border-orange-200 rounded mr-1"></span> Parcial</div>
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200 text-center text-xs font-bold text-gray-500 py-2">
                    <div>DOM</div>
                    <div>SEG</div>
                    <div>TER</div>
                    <div>QUA</div>
                    <div>QUI</div>
                    <div>SEX</div>
                    <div>SÁB</div>
                </div>
                <div class="grid grid-cols-7 text-sm">
                    @foreach($calendarData as $day)
                    @php
                    $bgClass = 'bg-white';
                    $textClass = 'text-gray-700';
                    $borderClass = 'border-gray-100'; // Default border

                    if (!$day['in_month']) {
                    $bgClass = 'bg-gray-50 text-gray-300';
                    } else {
                    if ($day['type'] === 'weekend') {
                    $bgClass = 'bg-gray-100 text-gray-500';
                    } elseif ($day['type'] === 'holiday') {
                    $bgClass = 'bg-blue-100 text-blue-800';
                    $borderClass = 'border-blue-200';
                    } elseif ($day['type'] === 'recess') {
                    $bgClass = 'bg-red-100 text-red-800';
                    $borderClass = 'border-red-200';
                    } elseif ($day['type'] === 'partial') {
                    $bgClass = 'bg-orange-100 text-orange-800';
                    $borderClass = 'border-orange-200';
                    }
                    }
                    @endphp

                    <div class="min-h-[80px] p-2 border-r border-b {{ $borderClass }} {{ $bgClass }} relative group">
                        <span class="font-bold {{ !$day['in_month'] ? 'opacity-50' : '' }}">{{ $day['day'] }}</span>

                        @if($day['label'] && $day['in_month'])
                        <div class="mt-1 text-[10px] leading-tight font-medium break-words">
                            {{ Str::limit($day['label'], 20) }}
                        </div>
                        <div class="absolute z-10 hidden group-hover:block bg-black text-white text-xs rounded p-1 bottom-1 left-1 right-1 text-center shadow-lg">
                            {{ $day['label'] }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 text-right">
                <x-secondary-button x-on:click="$dispatch('close')">Fechar</x-secondary-button>
            </div>
        </div>
    </x-modal>

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