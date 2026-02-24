<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Relógios Físicos (REPs)</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200 h-fit">
                <h3 class="text-lg font-bold mb-4">Novo Relógio</h3>
                <p class="text-xs text-gray-500 mb-4">Insira o Número de Série (NSR) exato do equipamento para que a comunicação Push funcione.</p>
                <form action="{{ route('devices.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <x-input-label value="Nome (Ex: Relógio Sec. Saúde)" />
                        <x-text-input name="name" class="w-full mt-1" required/>
                    </div>
                    <div class="mb-4">
                        <x-input-label value="Número de Série (NSR) *" />
                        <x-text-input name="serial_number" class="w-full mt-1" required placeholder="Ex: 000123456789"/>
                    </div>
                    <x-primary-button class="w-full justify-center">Salvar</x-primary-button>
                </form>
            </div>

            <div class="md:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden h-fit">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4 text-gray-700 font-bold">Equipamento</th>
                            <th class="p-4 text-gray-700 font-bold">NSR</th>
                            <th class="p-4 text-center text-gray-700 font-bold">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($devices as $dev)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-4 font-medium text-gray-900">{{ $dev->name }}</td>
                            <td class="p-4 text-gray-600">{{ $dev->serial_number }}</td>
                            <td class="p-4 flex items-center justify-center space-x-3">
                                
                                <form action="{{ route('devices.sync', $dev->id) }}" method="POST" onsubmit="return confirm('Sincronizar todos os servidores ativos com o relógio {{ $dev->name }}? Isso pode levar alguns minutos.')">
                                    @csrf
                                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 px-3 py-1.5 rounded-md text-xs font-bold transition flex items-center shadow-sm">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Sincronizar
                                    </button>
                                </form>

                                <form action="{{ route('devices.destroy', $dev->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este relógio?')">
                                    @csrf 
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-medium text-sm transition">
                                        Excluir
                                    </button>
                                </form>

                            </td>
                        </tr>
                        @endforeach
                        
                        @if($devices->isEmpty())
                        <tr>
                            <td colspan="3" class="p-6 text-center text-gray-500">Nenhum relógio cadastrado no momento.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                
                <div class="p-4 bg-gray-50 border-t text-xs text-gray-600 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><strong>URL do Push (Servidor):</strong> <span class="text-indigo-600 font-mono">http://{{ request()->getHost() }}:8080/api/controlid/push</span></span>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>