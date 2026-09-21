<?php

namespace Database\Seeders;

use App\Models\WhatsappFlujoConexion;
use App\Models\WhatsappFlujoNodo;
use Illuminate\Database\Seeder;

class WhatsappFlujoSeeder extends Seeder
{
    public function run(): void
    {
        if (WhatsappFlujoNodo::count() > 0) {
            return;
        }

        $n = fn (array $datos) => WhatsappFlujoNodo::create($datos);

        $inicio = $n([
            'clave' => 'menu_inicial', 'tipo' => WhatsappFlujoNodo::TIPO_MENU_INICIAL,
            'titulo' => 'Menú inicial',
            'mensaje' => "¡Bienvenido al sistema de Reservas Casa Blanca! 👋\n\n"
                . "*1* – Hacer una reserva\n*2* – Información\n\n"
                . 'Escribí el número que deseás hacer.',
            'pos_x' => 40, 'pos_y' => 40, 'editable' => true, 'eliminable' => false,
        ]);

        $info = $n([
            'clave' => 'info', 'tipo' => WhatsappFlujoNodo::TIPO_MENSAJE_FINAL,
            'titulo' => 'Información',
            'mensaje' => 'Estamos ubicados en Casa Blanca. Escribí *1* para hacer una reserva.',
            'pos_x' => 340, 'pos_y' => 220, 'editable' => true, 'eliminable' => false,
        ]);

        $nombre = $n([
            'clave' => 'pedir_nombre', 'tipo' => WhatsappFlujoNodo::TIPO_PREGUNTA_TEXTO,
            'titulo' => 'Nombre completo', 'campo_destino' => 'nombre',
            'mensaje' => 'Por favor, escribí tus nombres y apellidos.',
            'pos_x' => 40, 'pos_y' => 220, 'editable' => true, 'eliminable' => false,
        ]);

        $cedula = $n([
            'clave' => 'pedir_cedula', 'tipo' => WhatsappFlujoNodo::TIPO_PREGUNTA_TEXTO,
            'titulo' => 'Cédula', 'campo_destino' => 'cedula',
            'mensaje' => 'Escribí tu número de cédula.',
            'pos_x' => 360, 'pos_y' => 40, 'editable' => true, 'eliminable' => false,
        ]);

        $fotoLateral = $n([
            'clave' => 'pedir_foto_lateral', 'tipo' => WhatsappFlujoNodo::TIPO_PREGUNTA_FOTO,
            'titulo' => 'Foto cédula', 'campo_destino' => 'foto_lateral',
            'mensaje' => 'Para confirmar tu identidad, brindanos una foto de tu cédula.',
            'pos_x' => 680, 'pos_y' => 40, 'editable' => true, 'eliminable' => false,
        ]);

        $semana = $n([
            'clave' => 'pedir_semana', 'tipo' => WhatsappFlujoNodo::TIPO_PREGUNTA_TEXTO,
            'titulo' => 'Elegir semana', 'campo_destino' => 'semana_texto',
            'mensaje' => "Elegí la semana que deseás reservar (lunes a domingo).\n\n"
                . "Escribí la fecha de inicio de semana (lunes).\nEjemplo: 9 de junio 2025",
            'pos_x' => 360, 'pos_y' => 400, 'editable' => true, 'eliminable' => false,
        ]);

        $local = $n([
            'clave' => 'elegir_local', 'tipo' => WhatsappFlujoNodo::TIPO_ELEGIR_LOCAL,
            'titulo' => 'Elegir local',
            'mensaje' => "Elegí el local en el que deseás reservar:\n\n"
                . "*1.* Casa Blanca (Cuartos del 1 al 27)\n*2.* Casa Blanca VIP (Cuartos del 1 al 15)\n\n"
                . 'Escribí 1 o 2.',
            'pos_x' => 680, 'pos_y' => 400, 'editable' => true, 'eliminable' => false,
        ]);

        $cuarto = $n([
            'clave' => 'pedir_cuarto', 'tipo' => WhatsappFlujoNodo::TIPO_PREGUNTA_TEXTO,
            'titulo' => 'Cuarto solicitado', 'campo_destino' => 'cuarto_texto',
            'mensaje' => "¿Qué cuarto deseás solicitar?\n\nEscribí solamente el número del cuarto. Ejemplo: 12",
            'pos_x' => 1000, 'pos_y' => 400, 'editable' => true, 'eliminable' => false,
        ]);

        $guardarCliente = $n([
            'clave' => 'guardar_cliente', 'tipo' => WhatsappFlujoNodo::TIPO_GUARDAR_CLIENTE,
            'titulo' => 'Guardar cliente (sistema)',
            'mensaje' => null,
            'pos_x' => 1320, 'pos_y' => 400, 'editable' => false, 'eliminable' => false,
        ]);

        $crearSolicitud = $n([
            'clave' => 'crear_solicitud', 'tipo' => WhatsappFlujoNodo::TIPO_CREAR_SOLICITUD,
            'titulo' => 'Enviar solicitud (sistema)',
            'mensaje' => "✅ Recibimos tu solicitud de reserva.\n\n"
                . "Estado: *PENDIENTE* — un administrador va a revisar la disponibilidad del cuarto que pediste "
                . 'y te vamos a escribir por acá con la respuesta.',
            'pos_x' => 1640, 'pos_y' => 400, 'editable' => true, 'eliminable' => false,
        ]);

        // --- Plantillas que el admin dispara manualmente desde el panel de Solicitudes ---
        // No forman parte de la cadena automática: el motor las usa como texto (con
        // {local} {cuarto} {semana} como variables) cuando el admin aprueba/rechaza/confirma.

        $aprobada = $n([
            'clave' => 'plantilla_aprobada', 'tipo' => WhatsappFlujoNodo::TIPO_PLANTILLA_ADMIN,
            'titulo' => 'Plantilla: aprobada',
            'mensaje' => "✅ Tu solicitud para el cuarto {cuarto} de *{local}* fue *APROBADA*.\n\n"
                . "Para asegurar la reserva, depositá la prenda de \$20:\n\n"
                . "🏦 Banco: Banco de Guayaquil\n💳 Cuenta: 1234567890\n👤 Titular: Casa Blanca I\n💵 Valor: \$20\n\n"
                . "Al finalizar la semana se devuelve la prenda.\n\n"
                . 'Luego enviá por acá la foto del comprobante de depósito.',
            'pos_x' => 1960, 'pos_y' => 220, 'editable' => true, 'eliminable' => false,
        ]);

        $rechazada = $n([
            'clave' => 'plantilla_rechazada', 'tipo' => WhatsappFlujoNodo::TIPO_PLANTILLA_ADMIN,
            'titulo' => 'Plantilla: rechazada',
            'mensaje' => "❌ Tu solicitud para el cuarto {cuarto} de *{local}* no está disponible para la semana elegida.\n\n"
                . 'Escribí *1* si querés intentar con otro cuarto u otra semana.',
            'pos_x' => 1960, 'pos_y' => 400, 'editable' => true, 'eliminable' => false,
        ]);

        $confirmada = $n([
            'clave' => 'plantilla_confirmada', 'tipo' => WhatsappFlujoNodo::TIPO_PLANTILLA_ADMIN,
            'titulo' => 'Plantilla: confirmada',
            'mensaje' => "🎉 *¡Reserva confirmada!*\n\n"
                . "Tu habitación quedó reservada correctamente.\n\n"
                . "🏢 Local: {local}\n🚪 Cuarto: {cuarto}\n📅 Semana: {semana}{prenda}\n\n"
                . "*Importante:* debés estar en el local el día *LUNES a las 11:00 AM* para la entrega de la habitación.",
            'pos_x' => 1960, 'pos_y' => 580, 'editable' => true, 'eliminable' => false,
        ]);

        $esperarComprobante = $n([
            'clave' => 'esperar_comprobante_prenda', 'tipo' => WhatsappFlujoNodo::TIPO_ESPERAR_COMPROBANTE_PRENDA,
            'titulo' => 'Esperar comprobante (sistema)',
            'mensaje' => null,
            'pos_x' => 2280, 'pos_y' => 220, 'editable' => false, 'eliminable' => false,
        ]);

        $comprobanteRecibido = $n([
            'clave' => 'comprobante_recibido', 'tipo' => WhatsappFlujoNodo::TIPO_MENSAJE_FINAL,
            'titulo' => 'Comprobante recibido',
            'mensaje' => 'Recibí el comprobante ✅. Un administrador lo va a verificar y te confirmamos la reserva.',
            'pos_x' => 2600, 'pos_y' => 220, 'editable' => true, 'eliminable' => false,
        ]);

        $esperarCuartoSugerido = $n([
            'clave' => 'esperar_cuarto_sugerido', 'tipo' => WhatsappFlujoNodo::TIPO_ESPERAR_CUARTO_SUGERIDO,
            'titulo' => 'Esperar cuarto sugerido (sistema)',
            'mensaje' => null,
            'pos_x' => 2280, 'pos_y' => 760, 'editable' => false, 'eliminable' => false,
        ]);

        $cuartoSugeridoRecibido = $n([
            'clave' => 'cuarto_sugerido_recibido', 'tipo' => WhatsappFlujoNodo::TIPO_MENSAJE_FINAL,
            'titulo' => 'Cuarto sugerido elegido',
            'mensaje' => '¡Perfecto! Actualizamos tu solicitud con ese cuarto ✅. En breve un administrador la revisa.',
            'pos_x' => 2600, 'pos_y' => 760, 'editable' => true, 'eliminable' => false,
        ]);

        $c = fn ($origen, $destino, $valor = null, $etiqueta = null) => WhatsappFlujoConexion::create([
            'nodo_origen_id' => $origen->id, 'nodo_destino_id' => $destino->id, 'valor' => $valor, 'etiqueta' => $etiqueta,
        ]);

        $c($inicio, $nombre, '1', 'Reserva');
        $c($inicio, $nombre, 'reserva');
        $c($inicio, $nombre, 'reservar');
        $c($inicio, $info, '2', 'Info');
        $c($inicio, $info, 'info');
        $c($info, $inicio, null, 'Volver al inicio');

        $c($nombre, $cedula);
        $c($cedula, $fotoLateral);
        $c($fotoLateral, $semana);
        $c($semana, $local);
        $c($local, $cuarto);
        $c($cuarto, $guardarCliente);
        $c($guardarCliente, $crearSolicitud);

        // "esperar_comprobante_prenda" solo se activa cuando el admin aprueba desde el
        // panel (deja al cliente "parado" ahí). Al llegar la foto, avisa que se recibió
        // y ahí se corta: la confirmación final ("plantilla_confirmada") la dispara el
        // admin a mano después de revisar el comprobante, no el motor automáticamente.
        $c($esperarComprobante, $comprobanteRecibido);

        // "esperar_cuarto_sugerido" solo se activa cuando el admin le sugiere cuartos
        // alternativos a una solicitud pendiente desde el panel de Solicitudes. Si el
        // cliente responde con uno de los cuartos sugeridos, se actualiza la solicitud
        // (local/cuarto) y se corta acá — la aprueba o rechaza el admin como siempre.
        $c($esperarCuartoSugerido, $cuartoSugeridoRecibido);
    }
}
