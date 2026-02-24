<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestão de Servidores') }}
            </h2>
            
            <a href="{{ route('employees.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                + Novo Servidor
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex flex-col md:flex-row gap-6">
                
                <div class="w-full md:w-1/4">
                    <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-200 h-fit sticky top-6">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2 uppercase text-xs tracking-wider">
                            Estrutura Organizacional
                        </h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li>
                                <a href="{{ route('employees.index') }}" 
                                   class="flex items-center p-2 rounded-md transition-colors {{ !request('department_id') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'hover:bg-gray-50' }}">
                                    <span>👥 Todos os Servidores</span>
                                </a>
                            </li>
                            
                            @foreach($secretariats as $sec)
                                <li class="pt-2">
                                    <a href="{{ route('employees.index', ['department_id' => $sec->id]) }}" 
                                       class="flex items-center p-2 rounded-md transition-colors {{ request('department_id') == $sec->id ? 'bg-indigo-50 text-indigo-700 font-bold' : 'hover:bg-gray-50 font-medium text-gray-800' }}">
                                        <span>🏢 {{ $sec->name }}</span>
                                    </a>
                                    
                                    @if($sec->children->count() > 0)
                                        <ul class="ml-4 mt-1 space-y-1 border-l-2 border-indigo-100 pl-3">
                                            @foreach($sec->children as $child)
                                                <li>
                                                    <a href="{{ route('employees.index', ['department_id' => $child->id]) }}" 
                                                       class="block p-1 rounded-md transition-colors {{ request('department_id') == $child->id ? 'text-indigo-700 font-bold' : 'hover:text-indigo-600' }}">
                                                        ↳ {{ $child->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="w-full md:w-3/4 bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="overflow-x-auto p-6">
                        <table class="w-full text-sm text-left text-gray-600">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nome / Departamento</th>
                                    <th scope="col" class="px-6 py-3 text-center">PIS</th>
                                    <th scope="col" class="px-6 py-3 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($employees as $emp)
                                    <tr class="bg-white hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900">{{ $emp->name }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                {{ $emp->department->name ?? 'Sem Departamento' }}
                                                @if($emp->department && $emp->department->parent)
                                                    <span class="text-gray-300 mx-1">|</span> {{ $emp->department->parent->name }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center font-mono text-xs">{{ $emp->pis }}</td>
                                        
                                        <td class="px-6 py-4 flex justify-end items-center space-x-4">
                                            <a href="{{ route('admin.timesheet.report', $emp->id) }}" target="_blank" class="text-green-600 hover:text-green-900 font-medium underline decoration-green-200 underline-offset-4">
                                                Espelho
                                            </a>
                                            
                                            <a href="{{ route('employees.edit', $emp->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium underline decoration-indigo-200 underline-offset-4">
                                                Editar
                                            </a>
                                            
                                            <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('Excluir este servidor?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 font-medium underline decoration-red-200 underline-offset-4">
                                                    Excluir
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                                <p>Nenhum servidor encontrado para este filtro.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($employees->hasPages())
                        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                            {{ $employees->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>