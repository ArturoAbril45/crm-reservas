<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Interpreta el texto libre en español que el cliente escribe por WhatsApp para
 * la semana que quiere reservar ("28 de septiembre", "14/09/2026", "esta
 * semana"...). Se usa tanto para validar la respuesta en el momento (ver
 * WhatsappController::recibirPreguntaTexto) como para el anclaje automático
 * (ver SolicitudReservaWhatsappController::anclarAutomatico).
 */
class FechaLibreParser
{
    private const MESES = [
        'enero' => 'january', 'febrero' => 'february', 'marzo' => 'march',
        'abril' => 'april', 'mayo' => 'may', 'junio' => 'june', 'julio' => 'july',
        'agosto' => 'august', 'septiembre' => 'september', 'setiembre' => 'september',
        'octubre' => 'october', 'noviembre' => 'november', 'diciembre' => 'december',
    ];

    /**
     * Si lo que se entendió ya pasó hace más de una semana, asume que el cliente
     * se refiere a esa misma fecha del año que viene (nadie pide reservar una
     * semana que ya pasó) en vez de dejarla tal cual. Si no se puede entender
     * nada del texto, devuelve la fecha de hoy.
     */
    public static function parse(?string $texto): Carbon
    {
        $fecha = self::intentar(trim((string) $texto));

        if (! $fecha) {
            return now();
        }

        if ($fecha->lt(now()->subWeek())) {
            $fecha = $fecha->copy()->addYear();
        }

        return $fecha;
    }

    /**
     * Igual que intentar(), pero público — sin la corrección de "sumar un año si
     * ya pasó". Se usa para VALIDAR lo que el cliente escribe en el momento (ver
     * WhatsappController::recibirPreguntaTexto): ahí no hay que adivinar nada, hay
     * que avisarle que esa fecha ya pasó y que escriba una semana real. Devuelve
     * null si el texto no se pudo entender como fecha en absoluto (texto
     * ambiguo tipo "esta semana" no cuenta como "fecha pasada").
     */
    public static function parseSinCorregir(?string $texto): ?Carbon
    {
        return self::intentar(trim((string) $texto));
    }

    private static function intentar(string $texto): ?Carbon
    {
        if ($texto === '') {
            return null;
        }

        // Fecha numérica día/mes/año (como se escribe normalmente en español) hay
        // que interpretarla así explícitamente — Carbon::parse() por defecto asume
        // el formato americano mes/día/año para fechas con barra o guión.
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $texto, $m)) {
            try {
                return Carbon::createFromFormat('d/m/Y', "{$m[1]}/{$m[2]}/{$m[3]}")->startOfDay();
            } catch (\Throwable $e) {
                // sigue con los otros intentos
            }
        }

        try {
            return Carbon::parse($texto);
        } catch (\Throwable $e) {
            // sigue abajo con la traducción de meses en español
        }

        $traducido = str_ireplace(array_keys(self::MESES), array_values(self::MESES), $texto);
        $traducido = preg_replace('/\bde\b/i', '', $traducido);

        try {
            return Carbon::parse($traducido);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
