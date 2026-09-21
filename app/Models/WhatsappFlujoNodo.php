<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappFlujoNodo extends Model
{
    protected $table = 'whatsapp_flujo_nodos';

    protected $fillable = [
        'clave', 'tipo', 'titulo', 'mensaje', 'imagen', 'campo_destino', 'pos_x', 'pos_y', 'editable', 'eliminable',
    ];

    protected $casts = [
        'editable' => 'boolean',
        'eliminable' => 'boolean',
    ];

    // Tipos de nodo soportados por el motor del bot.
    public const TIPO_MENU_INICIAL = 'menu_inicial';
    public const TIPO_PREGUNTA_TEXTO = 'pregunta_texto';
    public const TIPO_PREGUNTA_FOTO = 'pregunta_foto';
    public const TIPO_GUARDAR_CLIENTE = 'guardar_cliente';
    public const TIPO_MENSAJE_FINAL = 'mensaje_final';
    public const TIPO_ELEGIR_LOCAL = 'elegir_local';
    public const TIPO_CREAR_SOLICITUD = 'crear_solicitud';
    public const TIPO_ESPERAR_COMPROBANTE_PRENDA = 'esperar_comprobante_prenda';
    public const TIPO_PLANTILLA_ADMIN = 'plantilla_admin';
    public const TIPO_ESPERAR_CUARTO_SUGERIDO = 'esperar_cuarto_sugerido';

    // Tipos que, por diseño, no necesitan una conexión de salida (el aviso
    // de "cuadro sin salida" del editor los ignora).
    public const TIPOS_TERMINALES = [
        self::TIPO_CREAR_SOLICITUD,
        self::TIPO_ESPERAR_COMPROBANTE_PRENDA,
        self::TIPO_PLANTILLA_ADMIN,
        self::TIPO_ESPERAR_CUARTO_SUGERIDO,
    ];

    public static function tiposDisponibles(): array
    {
        return [
            self::TIPO_PREGUNTA_TEXTO => 'Pregunta (texto)',
            self::TIPO_PREGUNTA_FOTO => 'Pregunta (foto)',
            self::TIPO_MENSAJE_FINAL => 'Mensaje final',
        ];
    }

    public function conexionesSalida()
    {
        return $this->hasMany(WhatsappFlujoConexion::class, 'nodo_origen_id');
    }
}
