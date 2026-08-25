<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">Iniciar sesión</h1>
        <p class="mt-1.5 text-sm text-gray-500">Ingresá tus datos para acceder al panel.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="tucorreo@empresa.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Contraseña')" class="mb-0" />
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-orange-500 hover:text-orange-600" href="{{ route('password.request') }}">
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </a>
                @endif
            </div>

            <x-text-input id="password" class="mt-1.5"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="flex items-center gap-2">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-blue-700 focus:ring-blue-700" name="remember">
            <span class="text-sm text-gray-600">{{ __('Recordarme') }}</span>
        </label>

        <x-primary-button>
            {{ __('Ingresar') }}
        </x-primary-button>
    </form>
</x-guest-layout>
