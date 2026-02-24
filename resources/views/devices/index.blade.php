<x-app-layout><x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Relógios Físicos (REPs)</h2></x-slot>
<div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200 h-fit">
        <h3 class="text-lg font-bold mb-4">Novo Relógio</h3>
        <p class="text-xs text-gray-500 mb-4">Insira o Número de Série (NSR) exato do equipamento para que a comunicação Push funcione.</p>
        <form action="{{ route('devices.store') }}" method="POST">@csrf
            <div class="mb-4"><x-input-label value="Nome (Ex: Relógio Sec. Saúde)" /><x-text-input name="name" class="w-full mt-1" required/></div>
            <div class="mb-4"><x-input-label value="Número de Série (NSR) *" /><x-text-input name="serial_number" class="w-full mt-1" required placeholder="Ex: 000123456789"/></div>
            <x-primary-button class="w-full justify-center">Salvar</x-primary-button>
        </form>
    </div>
    <div class="md:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full text-left text-sm"><thead class="bg-gray-50 border-b"><tr><th class="p-4">Equipamento</th><th class="p-4">NSR</th><th class="p-4 text-center">Ações</th></tr></thead>
        <tbody>@foreach($devices as $dev)<tr class="border-b"><td class="p-4 font-medium">{{ $dev->name }}</td><td class="p-4">{{ $dev->serial_number }}</td>
            <td class="p-4 text-center"><form action="{{ route('devices.destroy', $dev) }}" method="POST">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Excluir</button></form></td></tr>@endforeach
        </tbody></table>
        <div class="p-4 bg-gray-50 border-t text-xs text-gray-600">
            <strong>URL do Push (Servidor):</strong> <span class="text-indigo-600">http://{{ request()->getHost() }}:8080/api/controlid/push</span>
        </div>
    </div>
</div></div></x-app-layout>