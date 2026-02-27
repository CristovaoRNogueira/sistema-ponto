<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Acesso Restrito</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="mb-4 text-red-500">
                        <svg class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Vínculo não encontrado</h3>
                    <p class="mt-2 text-gray-600">O seu usuário de login não está vinculado a nenhum cadastro de funcionário (CPF ou Email não conferem).</p>
                    <p class="mt-4 text-sm text-gray-500">Por favor, entre em contato com o RH da Prefeitura para regularizar o seu acesso.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>