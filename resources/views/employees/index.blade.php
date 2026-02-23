<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestão de Servidores') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex justify-between items-center mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Servidores Cadastrados</h3>
                    <p class="text-sm text-gray-500">Gerencie todos os funcionários da instituição.</p>
                </div>
                
                <a href="{{ route('employees.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-md shadow-md flex items-center text-sm font-medium transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    + Novo Servidor
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Nome</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">PIS</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $emp)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-bold text-gray-900">{{ $emp->name }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ $emp->pis }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.timesheet.report', $emp->id) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            Gerar Espelho
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 text-center text-gray-500">
                                        Nenhum servidor cadastrado ainda. Clique no botão "+ Novo Servidor" acima.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-200">
                    {{ $employees->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>