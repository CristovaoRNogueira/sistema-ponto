<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bem-vindo! Configure o seu Perfil de Acesso') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-8">

                <h3 class="text-lg font-bold text-gray-800 mb-2">Como deseja utilizar o sistema?</h3>
                <p class="text-sm text-gray-600 mb-6 border-b pb-4">Escolha se você é o administrador inicial implementando uma nova prefeitura, ou se é um gestor/servidor se vinculando a uma base já existente.</p>

                <form action="{{ route('company.store') }}" method="POST" x-data="{ 
                    setupType: 'existing',
                    companies: {{ $companies->toJson() }},
                    selectedCompany: '',
                    get availableDepartments() {
                        let comp = this.companies.find(c => c.id == this.selectedCompany);
                        return comp ? comp.departments : [];
                    }
                }">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <label class="border rounded-lg p-4 cursor-pointer transition-all flex flex-col" :class="setupType === 'existing' ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-gray-200 hover:bg-gray-50'">
                            <div class="flex items-center mb-2">
                                <input type="radio" name="setup_type" value="existing" x-model="setupType" class="text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 font-bold text-gray-800">Vincular a uma Instituição</span>
                            </div>
                            <span class="text-xs text-gray-500 ml-6">Sou Secretário, RH Setorial ou Servidor. Quero entrar na base já cadastrada.</span>
                        </label>

                        <label class="border rounded-lg p-4 cursor-pointer transition-all flex flex-col" :class="setupType === 'new' ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-gray-200 hover:bg-gray-50'">
                            <div class="flex items-center mb-2">
                                <input type="radio" name="setup_type" value="new" x-model="setupType" class="text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 font-bold text-gray-800">Cadastrar Nova Instituição</span>
                            </div>
                            <span class="text-xs text-gray-500 ml-6">Sou o TI/Admin Global. Quero registrar a Prefeitura do zero.</span>
                        </label>
                    </div>

                    <div x-show="setupType === 'existing'" x-transition class="space-y-4">
                        <div>
                            <x-input-label for="company_id" value="Selecione a Prefeitura/Instituição" />
                            <select id="company_id" name="company_id" x-model="selectedCompany" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">-- Selecione --</option>
                                <template x-for="company in companies" :key="company.id">
                                    <option :value="company.id" x-text="company.name"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="department_id" value="Sua Lotação / Secretaria" />
                            <select id="department_id" name="department_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">-- Geral / Todas (ou selecione uma Secretaria) --</option>
                                <template x-for="dept in availableDepartments" :key="dept.id">
                                    <option :value="dept.id" x-text="dept.name"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="role" value="Qual o seu Nível de Acesso?" />
                            <select id="role" name="role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="operator">Gestor / RH Setorial (Pode gerenciar o departamento escolhido acima)</option>
                                <option value="employee">Servidor Comum (Apenas visualiza próprio espelho)</option>
                            </select>
                        </div>
                    </div>

                    <div x-show="setupType === 'new'" x-transition class="space-y-4" style="display: none;">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4">
                            <p class="text-xs text-yellow-800"><strong>Atenção:</strong> Esta opção criará uma nova base de dados separada e concederá acesso de <strong>Administrador Global</strong>.</p>
                        </div>

                        <div>
                            <x-input-label for="name" value="Razão Social / Nome da Prefeitura" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <x-input-label for="cnpj" value="CNPJ" />
                            <x-text-input id="cnpj" name="cnpj" type="text" class="mt-1 block w-full" placeholder="00.000.000/0000-00" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 border-t pt-4">
                        <x-primary-button class="bg-indigo-600 hover:bg-indigo-700">
                            {{ __('Finalizar Configuração e Entrar') }}
                        </x-primary-button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>