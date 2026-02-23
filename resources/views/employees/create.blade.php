<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cadastrar Novo Servidor
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-6" x-data="{ tab: 'gerais' }">
                
                <div class="flex border-b border-gray-200 mb-6 space-x-6">
                    <button @click="tab = 'gerais'" :class="{ 'border-indigo-600 text-indigo-600': tab === 'gerais', 'border-transparent text-gray-500': tab !== 'gerais' }" class="pb-2 border-b-2 font-medium focus:outline-none transition">Info Gerais</button>
                    <button @click="tab = 'pessoais'" :class="{ 'border-indigo-600 text-indigo-600': tab === 'pessoais', 'border-transparent text-gray-500': tab !== 'pessoais' }" class="pb-2 border-b-2 font-medium focus:outline-none transition">Dados Pessoais</button>
                    <button @click="tab = 'acesso'" :class="{ 'border-indigo-600 text-indigo-600': tab === 'acesso', 'border-transparent text-gray-500': tab !== 'acesso' }" class="pb-2 border-b-2 font-medium focus:outline-none transition">Acesso & Relógio</button>
                </div>

                <form action="{{ route('employees.store') }}" method="POST">
                    @csrf
                    
                    <div x-show="tab === 'gerais'" class="space-y-4">
                        <div><x-input-label value="Nome Completo *" /> <x-text-input name="name" type="text" class="w-full mt-1" required/></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Departamento" />
                                <select name="department_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                    <option value="">Selecione...</option>
                                    @foreach($departments as $dept) <option value="{{ $dept->id }}">{{ $dept->name }}</option> @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label value="Jornada de Trabalho" />
                                <select name="shift_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                    <option value="">Selecione...</option>
                                    @foreach($shifts as $shift) <option value="{{ $shift->id }}">{{ $shift->name }}</option> @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div x-show="tab === 'pessoais'" class="space-y-4" style="display: none;">
                        <div class="grid grid-cols-2 gap-4">
                            <div><x-input-label value="PIS *" /> <x-text-input name="pis" type="number" class="w-full mt-1" required/></div>
                            <div><x-input-label value="CPF" /> <x-text-input name="cpf" type="text" class="w-full mt-1"/></div>
                            <div><x-input-label value="RG" /> <x-text-input name="rg" type="text" class="w-full mt-1"/></div>
                            <div><x-input-label value="Matrícula" /> <x-text-input name="registration_number" type="text" class="w-full mt-1"/></div>
                        </div>
                    </div>

                    <div x-show="tab === 'acesso'" class="space-y-4" style="display: none;">
                        <div>
                            <x-input-label value="Relógio de Destino (Push) *" class="text-indigo-600 font-bold" />
                            <select name="device_id" class="w-full mt-1 border-indigo-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Selecione um relógio para enviar a biometria/cadastro...</option>
                                @foreach($devices as $device) <option value="{{ $device->id }}">{{ $device->name }}</option> @endforeach
                            </select>
                        </div>
                        <div class="mt-4 pt-4 border-t">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="mobile_access" class="rounded border-gray-300 text-indigo-600 shadow-sm" value="1">
                                <span class="ms-2 text-gray-700">Autorizar Batida via Celular/App</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-primary-button>Salvar Servidor</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>