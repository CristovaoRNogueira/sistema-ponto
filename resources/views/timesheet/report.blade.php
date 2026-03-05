<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Espelho de Ponto: {{ $employee->name }}
            </h2>

            <div class="flex flex-wrap gap-2 print:hidden">
                @if(Auth::user()->isAdmin() || Auth::user()->isOperator())
                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-manual-punch')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-md shadow text-xs font-bold uppercase tracking-wider transition flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Batida Manual
                </button>

                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-absence')" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-md shadow text-xs font-bold uppercase tracking-wider transition flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Atestado
                </button>

                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-shift-exception')" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 rounded-md shadow text-xs font-bold uppercase tracking-wider transition flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    Troca / Escala
                </button>
                @endif

                <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-700 text-white px-3 py-2 rounded-md shadow text-xs font-bold uppercase tracking-wider flex items-center transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Imprimir
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

            <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-200 print:hidden">
                <form method="GET" action="{{ (Auth::user()->isAdmin() || Auth::user()->isOperator()) ? route('admin.timesheet.report', $employee->id) : route('employee.timesheet') }}" class="flex flex-col sm:flex-row items-end space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="w-full sm:w-48">
                        <x-input-label for="month" value="Mês de Apuração" />
                        <select name="month" id="month" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @php
                            $meses = [
                            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                            ];
                            $selectedMonth = request('month', \Carbon\Carbon::now()->month);
                            @endphp
                            @foreach($meses as $num => $nome)
                            <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>
                                {{ $nome }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-32">
                        <x-input-label for="year" value="Ano" />
                        <select name="year" id="year" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @php
                            $currentYear = \Carbon\Carbon::now()->year;
                            $selectedYear = request('year', $currentYear);
                            @endphp
                            @foreach(range($currentYear - 2, $currentYear + 1) as $y)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-auto">
                        <x-primary-button type="submit" class="bg-gray-800 w-full sm:w-auto justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Buscar Período
                        </x-primary-button>
                    </div>
                </form>
            </div>

            @php
            $diasFaltaIntegral = 0;
            $cargaMensalMin = 0;
            $totalTrabalhadoMin = 0;

            foreach($report as $day) {
            if ($day['status'] === 'absence' || ($day['status'] === 'delay' && $day['worked_formatted'] === '00:00' && !$day['is_weekend'] && $day['status'] !== 'holiday' && $day['status'] !== 'justified' && $day['status'] !== 'vacation')) {
            $diasFaltaIntegral++;
            }
            $cargaMensalMin += $day['expected_minutes'] ?? 0;
            $totalTrabalhadoMin += $day['worked_minutes'] ?? 0;
            }

            $saldoMensalMin = $totalTrabalhadoMin - $cargaMensalMin;
            $formatMin = fn($min) => sprintf('%02d:%02d', floor($min / 60), $min % 60);
            @endphp

            <div class="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-200 print:shadow-none print:border-b-2 print:border-gray-800 print:rounded-none">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 uppercase tracking-wider text-sm border-b pb-2">Dados do Servidor</h3>
                        <p class="text-sm text-gray-700 mb-1"><strong>Órgão/Empresa:</strong> {{ $employee->company->name ?? 'Prefeitura Municipal' }}</p>
                        <p class="text-sm text-gray-700 mb-1"><strong>CPF:</strong> {{ $employee->cpf }}</p>
                        <p class="text-sm text-gray-700 mb-1"><strong>Departamento:</strong> {{ $employee->department->name ?? 'Não vinculado' }}</p>

                        @php
                        $effectiveShiftName = 'NENHUMA JORNADA DEFINIDA';
                        $shiftSource = '';

                        if ($employee->shift) {
                        $effectiveShiftName = $employee->shift->name;
                        $shiftSource = '(Específica do Servidor)';
                        } elseif ($employee->department?->shift) {
                        $effectiveShiftName = $employee->department->shift->name;
                        $shiftSource = '(Herdada de: ' . $employee->department->name . ')';
                        } elseif ($employee->department?->parent?->shift) {
                        $effectiveShiftName = $employee->department->parent->shift->name;
                        $shiftSource = '(Herdada de: ' . $employee->department->parent->name . ')';
                        }
                        @endphp
                        <p class="text-sm text-gray-700 mb-1">
                            <strong>Jornada Vinculada:</strong>
                            <span class="{{ $effectiveShiftName === 'NENHUMA JORNADA DEFINIDA' ? 'text-red-600 font-bold' : '' }}">
                                {{ $effectiveShiftName }}
                            </span>
                            @if($shiftSource)
                            <span class="text-xs text-gray-500 italic ml-1 print:text-gray-800">{{ $shiftSource }}</span>
                            @endif
                        </p>
                        <p class="text-sm text-gray-700 mt-2"><strong>Mês Exibido:</strong> <span class="capitalize font-bold text-indigo-700">{{ $period }}</span></p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-gray-50 p-3 rounded border border-gray-200 print:border-gray-300 print:bg-white flex flex-col justify-center text-center">
                            <p class="text-[10px] text-gray-600 uppercase font-bold print:text-gray-800 leading-tight mb-1">Carga Mensal<br>Exigida</p>
                            <p class="text-xl font-black text-gray-900 print:text-black">{{ $formatMin($cargaMensalMin) }}</p>
                        </div>
                        <div class="bg-indigo-50 p-3 rounded border border-indigo-100 print:border-gray-300 print:bg-white flex flex-col justify-center text-center">
                            <p class="text-[10px] text-indigo-600 uppercase font-bold print:text-gray-800 leading-tight mb-1">Total de Horas<br>Trabalhadas</p>
                            <p class="text-xl font-black text-indigo-900 print:text-black">{{ $formatMin($totalTrabalhadoMin) }}</p>
                        </div>
                        <div class="{{ $saldoMensalMin >= 0 ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' }} p-3 rounded border print:border-gray-300 print:bg-white flex flex-col justify-center text-center">
                            <p class="text-[10px] {{ $saldoMensalMin >= 0 ? 'text-green-600' : 'text-red-600' }} uppercase font-bold print:text-gray-800 leading-tight mb-1">Saldo Líquido<br>do Mês</p>
                            <p class="text-xl font-black {{ $saldoMensalMin >= 0 ? 'text-green-900' : 'text-red-900' }} print:text-black">
                                {{ $saldoMensalMin > 0 ? '+' : ($saldoMensalMin < 0 ? '-' : '') }}{{ $formatMin(abs($saldoMensalMin)) }}
                            </p>
                        </div>
                        <div class="bg-red-50 p-3 rounded border border-red-100 print:border-gray-300 print:bg-white flex flex-col justify-center text-center shadow-sm">
                            <p class="text-[10px] text-red-600 uppercase font-black print:text-gray-800 leading-tight mb-1">Dias Faltados<br>(Integrais)</p>
                            <p class="text-xl font-black text-red-900 print:text-black">{{ $diasFaltaIntegral }} <span class="text-xs font-bold text-red-700">dias</span></p>
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

                            if($day['status'] === 'absence' || ($day['status'] === 'delay' && $day['worked_formatted'] === '00:00' && !$day['is_weekend'])) {
                            $rowClass = 'bg-red-50/70';
                            } elseif($day['status'] === 'divergent' || $day['status'] === 'incomplete') {
                            $rowClass='bg-yellow-50';
                            } elseif($day['status']==='holiday' ) {
                            $rowClass='bg-blue-50';
                            } elseif($day['status'] === 'vacation') {
                            $rowClass='bg-indigo-50/50';
                            } elseif($day['is_weekend']) {
                            $rowClass='bg-gray-100';
                            }
                            @endphp

                            <tr class="hover:bg-indigo-50 {{ $rowClass }} print:bg-white print:text-black">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-800">{{ $day['date'] }}</div>
                                    <div class="text-xs text-gray-500 capitalize">{{ $day['day_name'] }}</div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-bold text-xs uppercase tracking-wider">
                                        @if($day['status'] === 'normal')
                                        <span class="text-green-600">Normal</span>
                                        @elseif($day['status'] === 'overtime')
                                        <span class="text-green-600">Hora Extra</span>
                                        @elseif($day['status'] === 'absence' || ($day['status'] === 'delay' && $day['worked_formatted'] === '00:00'))
                                        <span class="text-red-600 font-black">Falta Integral</span>
                                        @elseif($day['status'] === 'delay')
                                        <span class="text-orange-500 font-bold">Atraso / Saída</span>
                                        @elseif($day['status'] === 'incomplete')
                                        <span class="text-yellow-600 font-bold">Falta Batida</span>
                                        @elseif($day['status'] === 'divergent')
                                        <span class="text-yellow-600 font-bold">Incompleto</span>
                                        @elseif($day['status'] === 'day_off')
                                        <span class="text-gray-500">Folga / DSR</span>
                                        @elseif($day['status'] === 'holiday')
                                        <span class="text-blue-600">Feriado</span>
                                        @elseif($day['status'] === 'justified')
                                        <span class="text-purple-600">Atestado/Licença</span>
                                        @elseif($day['status'] === 'vacation')
                                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 text-[11px] font-black uppercase rounded border border-indigo-200">
                                            FÉRIAS
                                        </span>
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
                                    @if(in_array($day['status'], ['normal', 'overtime', 'delay', 'absence', 'incomplete', 'divergent']))
                                    @if($day['balance_minutes'] > 0)
                                    <span class="text-green-600">+{{ $day['balance_formatted'] }}</span>
                                    @elseif($day['balance_minutes'] < 0)
                                        <span class="{{ $day['worked_formatted'] === '00:00' ? 'text-red-600' : 'text-orange-600' }}">
                                        -{{ $day['balance_formatted'] }}
                                        </span>
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

            @if(isset($shiftExceptions) && $shiftExceptions->count() > 0)
            <div class="mt-8 bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden print:hidden">
                <div class="bg-orange-50 px-4 py-3 border-b border-orange-100 flex justify-between items-center">
                    <h3 class="font-bold text-orange-800 uppercase text-sm">Trocas de Plantão / Exceções (Neste Mês)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-2 text-gray-600">Data Modificada</th>
                                <th class="px-4 py-2 text-gray-600">Tipo</th>
                                <th class="px-4 py-2 text-gray-600">Horários Configurados</th>
                                <th class="px-4 py-2 text-gray-600">Observação</th>
                                @if(Auth::user()->isAdmin() || Auth::user()->isOperator())
                                <th class="px-4 py-2 text-center text-gray-600">Ação</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($shiftExceptions as $exc)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-medium text-gray-900 font-mono">
                                    {{ \Carbon\Carbon::parse($exc->exception_date)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 font-bold">
                                    @if($exc->type === 'day_off')
                                    <span class="text-red-600">Folga Extra / Zerar</span>
                                    @elseif($exc->type === 'swap')
                                    <span class="text-blue-600">Troca (Novo Horário)</span>
                                    @else
                                    <span class="text-orange-600">Convocação Extra</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 font-mono text-xs">
                                    @if($exc->type === 'day_off')
                                    --:--
                                    @else
                                    {{ $exc->in_1 ?? '--' }} / {{ $exc->out_1 ?? '--' }}
                                    @if($exc->in_2) <br> {{ $exc->in_2 }} / {{ $exc->out_2 }} @endif
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $exc->observation ?? '-' }}</td>

                                @if(Auth::user()->isAdmin() || Auth::user()->isOperator())
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('admin.shift_exceptions.destroy', $exc->id) }}" onsubmit="return confirm('Tem certeza que deseja remover esta exceção? A jornada original será restaurada para este dia.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded text-xs font-bold uppercase transition shadow-sm border border-red-200">Excluir</button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if(isset($absences) && $absences->count() > 0)
            <div class="mt-8 bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden print:hidden">
                <div class="bg-purple-50 px-4 py-3 border-b border-purple-100 flex justify-between items-center">
                    <h3 class="font-bold text-purple-800 uppercase text-sm">Atestados e Licenças (Neste Mês)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-2 text-gray-600">Período Abonado</th>
                                <th class="px-4 py-2 text-gray-600">Motivo / CID</th>
                                @if(Auth::user()->isAdmin() || Auth::user()->isOperator())
                                <th class="px-4 py-2 text-center text-gray-600">Ação</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($absences as $abs)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($abs->start_date)->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($abs->end_date)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $abs->reason }}</td>
                                @if(Auth::user()->isAdmin() || Auth::user()->isOperator())
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('absences.destroy', $abs->id) }}" onsubmit="return confirm('Tem certeza que deseja excluir este atestado?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded text-xs font-bold uppercase transition shadow-sm border border-red-200">Excluir</button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if(isset($manualPunches) && $manualPunches->count() > 0)
            <div class="mt-8 bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden print:hidden">
                <div class="bg-indigo-50 px-4 py-3 border-b border-indigo-100 flex justify-between items-center">
                    <h3 class="font-bold text-indigo-800 uppercase text-sm">Batidas Manuais (Neste Mês)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-2 text-gray-600">Data e Hora da Batida</th>
                                <th class="px-4 py-2 text-gray-600">Justificativa</th>
                                @if(Auth::user()->isAdmin() || Auth::user()->isOperator())
                                <th class="px-4 py-2 text-center text-gray-600">Ação</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($manualPunches as $punch)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-medium text-gray-900 font-mono">
                                    {{ \Carbon\Carbon::parse($punch->punch_time)->format('d/m/Y \à\s H:i') }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $punch->justification ?? 'Inserido manualmente pelo RH' }}</td>
                                @if(Auth::user()->isAdmin() || Auth::user()->isOperator())
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('punch-logs.destroy', $punch->id) }}" onsubmit="return confirm('Tem certeza que deseja excluir esta batida?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded text-xs font-bold uppercase transition shadow-sm border border-red-200">Excluir</button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

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

    @if(Auth::user()->isAdmin() || Auth::user()->isOperator())
    <x-modal name="add-manual-punch" focusable>
        <form method="POST" action="{{ route('timesheet.manual-punch', $employee->id) }}" class="p-6">
            @csrf
            <h2 class="text-lg font-bold text-gray-900 mb-2">Inserir Batida Manual</h2>
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
                <x-text-input id="justification" name="justification" type="text" class="mt-1 block w-full" placeholder="Ex: Esqueceu, Trabalho Externo..." required />
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="bg-indigo-600 hover:bg-indigo-700">Salvar Batida</x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="add-absence" focusable>
        <form method="POST" action="{{ route('timesheet.absence', $employee->id) }}" class="p-6">
            @csrf
            <h2 class="text-lg font-bold text-gray-900 mb-2">Registrar Atestado/Licença</h2>
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
                <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" placeholder="Ex: Atestado Médico, Licença..." required />
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="bg-purple-600 hover:bg-purple-700">Salvar</x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="add-shift-exception" focusable>
        <form method="POST" action="{{ route('admin.shift_exceptions.store') }}" class="p-6" x-data="{ type: 'swap' }">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">

            <h2 class="text-lg font-bold text-gray-900 mb-2">Troca de Plantão / Exceção</h2>
            <p class="text-xs text-gray-500 mb-4">Use para escalas 12x36 (trocar folga por dia trabalhado) ou convocações extras.</p>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <x-input-label for="exception_date" value="Data da Ocorrência" />
                    <x-text-input id="exception_date" name="exception_date" type="date" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="type" value="Tipo de Exceção" />
                    <select name="type" id="type" x-model="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500" required>
                        <option value="swap">Troca de Dia (Novo Horário)</option>
                        <option value="day_off">Folga Extra (Zerar Dia)</option>
                        <option value="extra">Convocação Extra</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-2 mb-4" x-show="type !== 'day_off'" x-transition>
                <div>
                    <x-input-label for="in_1" value="Ent 1" />
                    <x-text-input id="in_1" name="in_1" type="time" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="out_1" value="Sai 1" />
                    <x-text-input id="out_1" name="out_1" type="time" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="in_2" value="Ent 2" />
                    <x-text-input id="in_2" name="in_2" type="time" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="out_2" value="Sai 2" />
                    <x-text-input id="out_2" name="out_2" type="time" class="mt-1 block w-full" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="observation" value="Observação / Motivo" />
                <x-text-input id="observation" name="observation" type="text" class="mt-1 block w-full" placeholder="Ex: Troca com Fulano, Plantão Extra..." />
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="bg-orange-600 hover:bg-orange-700">Salvar Troca</x-primary-button>
            </div>
        </form>
    </x-modal>
    @endif

    <style>
        @media print {
            body {
                background-color: white !important;
            }

            nav {
                display: none !important;
            }

            .min-h-screen {
                background-color: white !important;
            }

            @page {
                margin: 1cm;
            }
        }
    </style>
</x-app-layout>