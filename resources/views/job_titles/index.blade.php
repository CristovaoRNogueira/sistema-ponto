<x-app-layout><x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Cargos Organizacionais</h2></x-slot>
<div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 shadow-sm rounded-lg border border-gray-200 h-fit">
        <h3 class="text-lg font-bold mb-4">Novo Cargo</h3>
        <form action="{{ route('job-titles.store') }}" method="POST">@csrf
            <div class="mb-4"><x-input-label value="Nome do Cargo *" /><x-text-input name="name" class="w-full mt-1" required placeholder="Ex: Enfermeiro Padrão"/></div>
            <div class="mb-4"><x-input-label value="CBO (Opcional)" /><x-text-input name="cbo" class="w-full mt-1" placeholder="Ex: 2235-05"/></div>
            <x-primary-button class="w-full justify-center">Salvar</x-primary-button>
        </form>
    </div>
    <div class="md:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full text-left text-sm"><thead class="bg-gray-50 border-b"><tr><th class="p-4">Cargo</th><th class="p-4">CBO</th><th class="p-4 text-center">Ações</th></tr></thead>
        <tbody>@foreach($jobTitles as $job)<tr class="border-b"><td class="p-4 font-medium">{{ $job->name }}</td><td class="p-4">{{ $job->cbo }}</td>
            <td class="p-4 text-center"><form action="{{ route('job-titles.destroy', $job) }}" method="POST">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Excluir</button></form></td></tr>@endforeach
        </tbody></table>
    </div>
</div></div></x-app-layout>