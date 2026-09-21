<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if ($motivo = Auth::user()->motivoBloqueoAcceso()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages(['email' => $motivo]);
        }

        $request->session()->regenerate();

        // Los usuarios que pueden trabajar en más de una sucursal eligen con cuál
        // entrar justo después de loguearse; los que están fijos a una sola sucursal
        // (sin el permiso cambiar_sucursal) van directo a Reservas con la que ya tengan.
        $destino = Auth::user()->puedeVer('cambiar_sucursal')
            ? route('sucursales.seleccionar', absolute: false)
            : route('reservas', absolute: false);

        return redirect()->intended($destino);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
