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
                        <x-text-input name="name" class="w-full mt-1" required />
                    </div>
                    <div class="mb-4">
                        <x-input-label value="Número de Série (NSR) *" />
                        <x-text-input name="serial_number" class="w-full mt-1" required placeholder="Ex: 000123456789" />
                    </div>

                    <div class="mb-4">
                        <x-input-label value="Endereço IP (Local/VPN)" />
                        <x-text-input name="ip_address" class="w-full mt-1 font-mono" placeholder="Ex: 192.168.1.50" />
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
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="p-4 text-gray-700 font-bold">Status Rede</th>
                                <th class="p-4 text-gray-700 font-bold">Equipamento</th>
                                <th class="p-4 text-gray-700 font-bold text-center">Status Bobina</th>
                                <th class="p-4 text-center text-gray-700 font-bold">Ações Operacionais</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($devices as $dev)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4">
                                    @if(!$dev->ip_address)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        Sem IP Configurado
                                    </span>
                                    @elseif($dev->is_online)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        <span class="w-2 h-2 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                                        VPN Online
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200" title="{{ $dev->last_error }}">
                                        <span class="w-2 h-2 rounded-full bg-red-500 mr-1.5"></span>
                                        Offline
                                    </span>
                                    @endif
                                    <div class="text-[10px] text-gray-400 mt-1 font-mono">
                                        Última checagem: {{ $dev->last_seen_at ? $dev->last_seen_at->format('d/m/y H:i') : 'Nunca' }}
                                    </div>
                                </td>

                                <td class="p-4">
                                    <span class="font-bold text-gray-900 block">{{ $dev->name }}</span>
                                    <span class="text-xs text-gray-500 block uppercase">NSR: {{ $dev->serial_number }}</span>
                                    <span class="text-gray-600 block font-mono text-[11px] font-bold mt-0.5">{{ $dev->ip_address ?? 'Sem Rede' }}</span>
                                </td>

                                <td class="p-4 text-center">
                                    @if(!$dev->ip_address)
                                    <span class="text-gray-400 text-xs">-</span>
                                    @elseif($dev->paper_status === 'ok')
                                    <span class="text-green-600 font-bold text-xs flex items-center justify-center bg-green-50 px-2 py-1 rounded border border-green-100">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Normal
                                    </span>
                                    @elseif($dev->paper_status === 'low')
                                    <span class="text-yellow-600 font-bold text-xs flex items-center justify-center bg-yellow-50 px-2 py-1 rounded border border-yellow-200 shadow-sm">
                                        <svg class="w-4 h-4 mr-1 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        Acabando
                                    </span>
                                    @elseif($dev->paper_status === 'empty')
                                    <span class="text-red-600 font-black text-xs flex items-center justify-center bg-red-50 px-2 py-1 rounded border border-red-200 shadow-sm">
                                        <svg class="w-4 h-4 mr-1 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        SEM PAPEL
                                    </span>
                                    @else
                                    <span class="text-gray-500 text-xs">Desconhecido</span>
                                    @endif
                                </td>

                                <td class="p-4">
                                    <div class="flex items-center justify-end space-x-2">
                                        <form action="{{ route('devices.check-status', $dev->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" @if(!$dev->ip_address) disabled @endif class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 p-1.5 rounded transition disabled:opacity-50" title="Verificar Conexão e Papel Agora">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                        </form>

                                        <form action="{{ route('devices.sync', $dev->id) }}" method="POST" onsubmit="return confirm('Injetar todos os servidores ativos neste relógio?')">
                                            @csrf
                                            <button type="submit" @if(!$dev->ip_address) disabled @endif class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-1.5 rounded transition disabled:opacity-50" title="Sincronizar Servidores">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                </svg>
                                            </button>
                                        </form>

                                        <form action="{{ route('devices.import', $dev->id) }}" method="POST" onsubmit="return confirm('Puxar funcionários cadastrados no relógio para o sistema?')">
                                            @csrf
                                            <button type="submit" @if(!$dev->ip_address) disabled @endif class="text-emerald-600 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 p-1.5 rounded transition disabled:opacity-50" title="Importar do Relógio">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>
                                            </button>
                                        </form>

                                        <form action="{{ route('devices.destroy', $dev->id) }}" method="POST" onsubmit="return confirm('Excluir equipamento do sistema?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-1.5 rounded transition" title="Excluir Relógio">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-gray-500">Nenhum relógio cadastrado no momento.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>