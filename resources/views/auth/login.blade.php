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
    <main class="flex flex-1 overflow-hidden">

        <div class="w-full lg:w-1/2 flex flex-col justify-center px-8 sm:px-16 lg:px-24 xl:px-32 relative z-10 overflow-y-auto">

            <div class="w-full max-w-md mx-auto">

                <div class="lg:hidden mb-8 text-center">
                    <h1 class="text-2xl font-black text-indigo-700 dark:text-indigo-500 tracking-tight">Sistema de Ponto</h1>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-widest mt-1">Carinhanha - BA</p>
                </div>

                <div class="mb-10">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Acesso ao Sistema</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Insira as suas credenciais para gerenciar jornadas e pontos.</p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail de Acesso</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="seuemail@exemplo.com" class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 px-3 transition" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sua Senha</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password" placeholder=" " class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 px-3 transition" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="flex items-center cursor-pointer">
                            <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 cursor-pointer">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Manter logado</span>
                        </label>

                        @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 hover:underline transition">
                            Esqueceu a senha?
                        </a>
                        @endif
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                            Entrar no Sistema
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-800 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Primeiro acesso? Entre em contato com o Setor de TI.
                    </p>
                </div>

            </div>
        </div>

        <div class="hidden lg:flex lg:w-1/2 relative items-center justify-center bg-indigo-50 dark:bg-[#1D0002]">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-100 to-indigo-50 dark:from-gray-900 dark:to-black opacity-90"></div>

            <svg class="absolute inset-0 w-full h-full object-cover text-indigo-500/10 dark:text-indigo-600/20" viewBox="0 0 438 376" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <path d="M110.256 41.6337C108.061 38.1275 104.945 35.3731 100.905 33.3681C96.8667 31.3647 92.8016 30.3618 88.7131 30.3618C83.4247 30.3618 78.5885 31.3389 74.201 33.2923C69.8111 35.2456 66.0474 37.928 62.9059 41.3333C59.7643 44.7401 57.3198 48.6726 55.5754 53.1293C53.8287 57.589 52.9572 62.274 52.9572 67.1813C52.9572 72.1925 53.8287 76.8995 55.5754 81.3069C57.3191 85.7173 59.7636 89.6241 62.9059 93.0293C66.0474 96.4361 69.8119 99.1155 74.201 101.069C78.5885 103.022 83.4247 103.999 88.7131 103.999C92.8016 103.999 96.8667 102.997 100.905 100.994C104.945 98.9911 108.061 96.2359 110.256 92.7282V102.195H126.563V32.1642H110.256V41.6337Z" fill="currentColor" transform="scale(3) translate(-20, 20)" />
                <path d="M376.571 30.3656C356.603 30.3656 340.797 46.8497 340.797 67.1828C340.797 89.6597 356.094 104 378.661 104C391.29 104 399.354 99.1488 409.206 88.5848L398.189 80.0226C398.183 80.031 389.874 90.9895 377.468 90.9895C363.048 90.9895 356.977 79.3111 356.977 73.269H411.075C413.917 50.1328 398.775 30.3656 376.571 30.3656Z" fill="currentColor" transform="scale(2) translate(50, 40)" />
            </svg>

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

    <footer class="w-full border-t border-gray-200 dark:border-gray-800 py-4 text-center bg-white dark:bg-[#0a0a0a]">
        <p class="text-xs text-gray-500 dark:text-gray-600">
            © {{ date('Y') }} Prefeitura Municipal de Carinhanha.
            Desenvolvido por
            <a href="https://crnsistemas.com.br" target="_blank"
                class="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline transition">
                CRN Sistemas
            </a>.
        </p>
    </footer>
</body>

</html>