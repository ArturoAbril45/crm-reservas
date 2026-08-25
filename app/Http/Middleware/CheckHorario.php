<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckHorario
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($motivo = $user->motivoBloqueoAcceso())) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $motivo]);
        }

        return $next($request);
    }
}
