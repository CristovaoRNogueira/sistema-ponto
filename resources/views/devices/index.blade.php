<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Relógios Físicos (REPs)</h2>
    </x-slot>

    @if (session('success'))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm" role="alert">
                <span class="block sm:inline font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm" role="alert">
                <span class="block sm:inline font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200 h-fit">
                <h3 class="text-lg font-bold mb-4">Novo Relógio</h3>
                <p class="text-xs text-gray-500 mb-4 bg-blue-50 p-2 rounded">Para sincronizar servidores diretamente do painel, preencha o Endereço IP Local do relógio.</p>
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
                    
                    <div class="mb-4">
                        <x-input-label value="Endereço IP (Local/VPN)" />
                        <x-text-input name="ip_address" class="w-full mt-1 font-mono" placeholder="Ex: 192.168.1.50"/>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div>
                            <x-input-label value="Usuário do Relógio" />
                            <x-text-input name="username" value="admin" class="w-full mt-1 text-sm text-gray-500" />
                        </div>
                        <div>
                            <x-input-label value="Senha do Relógio" />
                            <x-text-input name="password" type="password" value="admin" class="w-full mt-1 text-sm text-gray-500" />
                        </div>
                    </div>

                    <x-primary-button class="w-full justify-center">Salvar Equipamento</x-primary-button>
                </form>
            </div>

            <div class="md:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden h-fit">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4 text-gray-700 font-bold">Equipamento</th>
                            <th class="p-4 text-gray-700 font-bold">NSR / IP</th>
                            <th class="p-4 text-center text-gray-700 font-bold">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $dev)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-4">
                                <span class="font-medium text-gray-900 block">{{ $dev->name }}</span>
                                @if($dev->ip_address)
                                    <span class="text-xs text-green-600 flex items-center mt-1">
                                        <span class="w-2 h-2 rounded-full bg-green-500 mr-1"></span> IP Local Ativo
                                    </span>
                                @else
                                    <span class="text-xs text-orange-500 flex items-center mt-1">
                                        <span class="w-2 h-2 rounded-full bg-orange-400 mr-1"></span> Sem IP Configurado
                                    </span>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="text-gray-600 block">NSR: {{ $dev->serial_number }}</span>
                                <span class="text-gray-600 block font-mono text-xs font-bold mt-1">IP: {{ $dev->ip_address ?? '---.---.---.---' }}</span>
                            </td>
                            <td class="p-4 flex items-center justify-center space-x-3 mt-2">
                                
                                <form action="{{ route('devices.sync', $dev->id) }}" method="POST" onsubmit="return confirm('Sincronizar {{ $dev->name }} agora? O sistema enviará os servidores via rede local (IP).')">
                                    @csrf
                                    @if($dev->ip_address)
                                        <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 px-3 py-1.5 rounded-md text-xs font-bold transition flex items-center shadow-sm">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            Sincronizar (IP)
                                        </button>
                                    @else
                                        <button type="button" disabled class="text-white bg-gray-400 px-3 py-1.5 rounded-md text-xs font-bold flex items-center cursor-not-allowed shadow-sm" title="Preencha o IP para sincronizar">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                            Sem IP
                                        </button>
                                    @endif
                                </form>

                                <form action="{{ route('devices.destroy', $dev->id) }}" method="POST" onsubmit="return confirm('Excluir este relógio? O histórico de ponto não será perdido.')">
                                    @csrf 
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-medium text-sm transition px-2 py-1 bg-red-50 rounded">
                                        Excluir
                                    </button>
                                </form>

                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="p-6 text-center text-gray-500">Nenhum relógio cadastrado no momento.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>