<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso - Sistema de Ponto Eletrônico</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/Carinhanha.webp') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,900" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
    <script src="https://cdn.tailwindcss.com"></script>
    @endif
</head>

<body class="font-sans antialiased text-gray-900 bg-white dark:bg-[#0a0a0a] min-h-screen flex flex-col">

    <!-- MAIN -->
    <main class="flex flex-1">

        <!-- COLUNA ESQUERDA (FORMULÁRIO) -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center px-8 sm:px-16 lg:px-24 xl:px-32 relative z-10 py-12">

            <div class="w-full max-w-md mx-auto">

                <!-- Título Mobile -->
                <div class="lg:hidden mb-8 text-center">
                    <h1 class="text-2xl font-black text-indigo-700 dark:text-indigo-500 tracking-tight">
                        Sistema de Ponto
                    </h1>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-widest mt-1">
                        Carinhanha - BA
                    </p>
                </div>

                <!-- Header -->
                <div class="mb-10">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Acesso ao Sistema
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Insira as suas credenciais para gerenciar jornadas e pontos.
                    </p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <!-- FORM -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="login" value="CPF ou E-mail" />
                        <x-text-input id="login"
                            class="block mt-1 w-full"
                            type="text"
                            name="login"
                            :value="old('login')"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder=" " />
                        <x-input-error :messages="$errors->get('login')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Sua Senha
                        </label>
                        <input id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder=" "
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 px-3 transition" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="flex items-center cursor-pointer">
                            <input id="remember_me"
                                type="checkbox"
                                name="remember"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                Manter logado
                            </span>
                        </label>
                    </div>

                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                            Entrar no Sistema
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-800 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Primeiro acesso ou esqueceu a senha? Entre em contato com o Setor de TI.
                    </p>
                </div>

            </div>
        </div>

        <!-- COLUNA DIREITA (DESKTOP) -->
        <div class="hidden lg:flex lg:w-1/2 relative items-center justify-center bg-indigo-50 dark:bg-[#1D0002]">

            <div class="absolute inset-0 bg-gradient-to-br from-indigo-100 to-indigo-50 dark:from-gray-900 dark:to-black opacity-90"></div>

            <div class="relative z-20 text-center px-12 lg:px-24 flex flex-col items-center">

                <div class="bg-white/50 dark:bg-black/20 p-6 rounded-2xl backdrop-blur-sm border border-white/20 shadow-xl mb-8 flex items-center justify-center">
                    <x-application-logo class="h-24" />
                </div>

                <h1 class="text-4xl xl:text-5xl font-black text-indigo-950 dark:text-white tracking-tight mb-4">
                    Sistema de Ponto<br>Eletrônico
                </h1>

                <div class="h-1 w-20 bg-indigo-500 rounded-full mb-6"></div>

                <p class="text-lg xl:text-xl text-indigo-800 dark:text-gray-300 font-medium leading-relaxed">
                    Prefeitura Municipal de Carinhanha
                </p>

                <p class="mt-4 text-sm text-indigo-600/80 dark:text-gray-500 font-medium">
                    Gestão transparente, relatórios precisos e controle de jornadas simplificado para o servidor.
                </p>
            </div>
        </div>

    </main>

    <!-- FOOTER FIXO NO FUNDO -->
    <footer class="mt-auto w-full border-t border-gray-200 dark:border-gray-800 py-4 text-center bg-white dark:bg-[#0a0a0a]">
        <p class="text-xs text-gray-500 dark:text-gray-600">
            © {{ date('Y') }} Prefeitura Municipal de Carinhanha.
            Desenvolvido por
            <a href="https://crnsistemas.com.br"
                target="_blank"
                class="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline transition">
                CRN Sistemas
            </a>.
        </p>
    </footer>

</body>

</html>