<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bem-vindo! Configure sua Empresa/Instituição') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="mb-4 text-gray-600">Para começar a usar o sistema de Ponto, você precisa registrar a instituição.</p>
                
                <form action="{{ route('company.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="name" value="Razão Social / Nome da Prefeitura" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required autofocus />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="cnpj" value="CNPJ" />
                        <x-text-input id="cnpj" name="cnpj" type="text" class="mt-1 block w-full" required placeholder="00.000.000/0000-00" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>
                            {{ __('Finalizar Configuração') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>