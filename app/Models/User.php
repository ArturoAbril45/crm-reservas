<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'permisos', 'horario_activo', 'hora_inicio', 'hora_fin', 'dias_trabajo', 'bloqueado_fecha'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permisos' => 'array',
            'horario_activo' => 'boolean',
            'bloqueado_fecha' => 'date',
        ];
    }

    public function puedeVer(string $modulo): bool
    {
        if ($modulo === 'reservas') {
            return true;
        }

        return in_array($modulo, $this->permisos ?? [], true);
    }

    /**
     * Devuelve el motivo por el que el acceso está bloqueado ahora mismo,
     * o null si el usuario puede entrar/seguir en la sesión.
     */
    public function motivoBloqueoAcceso(): ?string
    {
        if ($this->bloqueado_fecha && $this->bloqueado_fecha->isToday()) {
            return 'Tu acceso está bloqueado por hoy. Consultá con un administrador.';
        }

        if (! $this->horario_activo) {
            return null;
        }

        $ahora = now();
        $diasPermitidos = array_filter(array_map('trim', explode(',', (string) $this->dias_trabajo)), fn ($d) => $d !== '');

        [$horaInicio, $minInicio] = array_pad(explode(':', $this->hora_inicio ?: '00:00'), 2, '0');
        [$horaFin, $minFin] = array_pad(explode(':', $this->hora_fin ?: '23:59'), 2, '0');

        $inicioMin = ((int) $horaInicio) * 60 + (int) $minInicio;
        $finMin = ((int) $horaFin) * 60 + (int) $minFin;
        $ahoraMin = $ahora->hour * 60 + $ahora->minute;

        $diaHoy = (string) $ahora->dayOfWeek;
        $diaAnterior = (string) (($ahora->dayOfWeek + 6) % 7);

        if ($inicioMin === $finMin) {
            $dentroDeHorario = true;
            $diaQueAplica = $diaHoy;
        } elseif ($inicioMin < $finMin) {
            $dentroDeHorario = $ahoraMin >= $inicioMin && $ahoraMin < $finMin;
            $diaQueAplica = $diaHoy;
        } elseif ($ahoraMin >= $inicioMin) {
            $dentroDeHorario = true;
            $diaQueAplica = $diaHoy;
        } elseif ($ahoraMin < $finMin) {
            $dentroDeHorario = true;
            $diaQueAplica = $diaAnterior;
        } else {
            $dentroDeHorario = false;
            $diaQueAplica = $diaHoy;
        }

        if ($dentroDeHorario && in_array($diaQueAplica, $diasPermitidos, true)) {
            return null;
        }

        return 'Estás fuera de tu horario permitido de acceso.';
    }
}
