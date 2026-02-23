<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Cadastrar Novo Servidor') }}
            </h2>
            <a href="{{ route('employees.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">Voltar para a lista</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200" x-data="{ tab: 'gerais' }">
                
                <div class="flex border-b border-gray-200 bg-gray-50">
                    <button @click="tab = 'gerais'" :class="{ 'border-b-2 border-indigo-500 text-indigo-600 bg-white': tab === 'gerais', 'text-gray-500 hover:text-gray-700': tab !== 'gerais' }" class="px-6 py-4 text-sm font-medium focus:outline-none transition-colors">
                        Informações Gerais
                    </button>
                    <button @click="tab = 'pessoais'" :class="{ 'border-b-2 border-indigo-500 text-indigo-600 bg-white': tab === 'pessoais', 'text-gray-500 hover:text-gray-700': tab !== 'pessoais' }" class="px-6 py-4 text-sm font-medium focus:outline-none transition-colors">
                        Dados Pessoais
                    </button>
                    <button @click="tab = 'acesso'" :class="{ 'border-b-2 border-indigo-500 text-indigo-600 bg-white': tab === 'acesso', 'text-gray-500 hover:text-gray-700': tab !== 'acesso' }" class="px-6 py-4 text-sm font-medium focus:outline-none transition-colors">
                        Integração & App
                    </button>
                </div>

                <div class="p-6 text-gray-900">
                    <form action="{{ route('employees.store') }}" method="POST">
                        @csrf
                        
                        <div x-show="tab === 'gerais'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">
                            <div>
                                <x-input-label for="name" value="Nome Completo *" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required autofocus />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="department_id" value="Departamento / Secretaria" />
                                    <select id="department_id" name="department_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                                        <option value="">Selecione...</option>
                                        @if(isset($departments))
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="job_title_id" value="Cargo" />
                                    <select id="job_title_id" name="job_title_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                                        <option value="">Selecione...</option>
                                        @if(isset($jobTitles))
                                            @foreach($jobTitles as $job)
                                                <option value="{{ $job->id }}">{{ $job->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="shift_id" value="Jornada de Trabalho" />
                                    <select id="shift_id" name="shift_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                                        <option value="">Selecione...</option>
                                        @if(isset($shifts))
                                            @foreach($shifts as $shift)
                                                <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="cost_center_id" value="Centro de Custo" />
                                    <select id="cost_center_id" name="cost_center_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                                        <option value="">Selecione...</option>
                                        @if(isset($costCenters))
                                            @foreach($costCenters as $costCenter)
                                                <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div x-show="tab === 'pessoais'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6" style="display: none;">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="pis" value="PIS *" />
                                    <x-text-input id="pis" name="pis" type="number" class="mt-1 block w-full" required />
                                </div>
                                <div>
                                    <x-input-label for="cpf" value="CPF" />
                                    <x-text-input id="cpf" name="cpf" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <x-input-label for="rg" value="RG" />
                                    <x-text-input id="rg" name="rg" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <x-input-label for="registration_number" value="Matrícula" />
                                    <x-text-input id="registration_number" name="registration_number" type="text" class="mt-1 block w-full" />
                                </div>
                            </div>
                        </div>

                        <div x-show="tab === 'acesso'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6" style="display: none;">
                            
                            <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                                <x-input-label for="device_id" value="Relógio de Destino (Integração Push) *" class="text-indigo-800 font-bold" />
                                <p class="text-xs text-indigo-600 mb-2">A ordem de cadastro será enviada imediatamente para a memória deste relógio.</p>
                                <select id="device_id" name="device_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                                    <option value="">Selecione o relógio base...</option>
                                    @if(isset($devices))
                                        @foreach($devices as $dev)
                                            <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="text-md font-medium text-gray-800 mb-4">Acesso Aplicativo Mobile</h4>
                                
                                <div class="block mb-4">
                                    <label for="mobile_access" class="inline-flex items-center">
                                        <input id="mobile_access" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="mobile_access" value="1">
                                        <span class="ms-2 text-sm text-gray-600">Autorizar Marcação de Ponto via App (Geolocalização)</span>
                                    </label>
                                </div>

                                <div>
                                    <x-input-label for="app_password" value="Senha de Acesso (App/Web)" />
                                    <x-text-input id="app_password" name="app_password" type="password" class="mt-1 block w-full" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 border-t border-gray-200 pt-6">
                            <x-primary-button>
                                {{ __('Salvar Servidor') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>