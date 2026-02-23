<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Visão Geral - {{ Auth::user()->company->name ?? 'RH' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 font-medium">Servidores Ativos</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalEmployees }}</p>
                </div>
                </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Absenteísmo (Última Semana)</h3>
                    <canvas id="attendanceChart" height="120"></canvas>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Últimas Batidas</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-4 py-2">Data/Hora</th>
                                    <th class="px-4 py-2">Servidor</th>
                                    <th class="px-4 py-2">Relógio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($punches as $punch)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2">{{ $punch->punch_time->format('d/m/Y H:i:s') }}</td>
                                        <td class="px-4 py-2 font-medium">{{ $punch->employee->name }}</td>
                                        <td class="px-4 py-2 text-xs text-gray-500">{{ $punch->device->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [
                        { label: 'Presenças', data: {!! json_encode($chartPresences) !!}, backgroundColor: '#4F46E5', borderRadius: 4 },
                        { label: 'Faltas', data: {!! json_encode($chartAbsences) !!}, backgroundColor: '#EF4444', borderRadius: 4 }
                    ]
                }
            });
        });
    </script>
</x-app-layout>