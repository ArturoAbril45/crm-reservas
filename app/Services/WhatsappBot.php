<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsappBot
{
    protected function client()
    {
        return Http::baseUrl(config('whatsapp.bot_url'))
            ->withToken(config('whatsapp.bot_token'))
            ->timeout(10);
    }

    public function estado(): array
    {
        try {
            $response = $this->client()->get('/estado');
        } catch (Throwable $e) {
            return ['conectado' => false, 'numero' => null, 'qr' => null, 'error' => 'No se pudo contactar al bot.'];
        }

        if (! $response->successful()) {
            return ['conectado' => false, 'numero' => null, 'qr' => null, 'error' => 'No se pudo contactar al bot.'];
        }

        return $response->json();
    }

    public function conectar(): array
    {
        try {
            $response = $this->client()->post('/conectar');
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'No se pudo contactar al bot.'];
        }

        return $response->successful()
            ? $response->json()
            : ['ok' => false, 'error' => 'No se pudo contactar al bot.'];
    }

    public function desconectar(): array
    {
        try {
            $response = $this->client()->post('/desconectar');
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'No se pudo contactar al bot.'];
        }

        return $response->successful()
            ? $response->json()
            : ['ok' => false, 'error' => 'No se pudo contactar al bot.'];
    }

    public function enviar(string $numero, string $mensaje, ?string $imagenUrl = null): array
    {
        try {
            $response = $this->client()->post('/enviar', [
                'numero'     => $numero,
                'mensaje'    => $mensaje,
                'imagen_url' => $imagenUrl,
            ]);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'No se pudo contactar al bot.'];
        }

        return $response->successful()
            ? $response->json()
            : ['ok' => false, 'error' => 'No se pudo contactar al bot.'];
    }
}
