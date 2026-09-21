<?php

return [
    // URL base del bot de WhatsApp (Node.js con Baileys), corriendo en el VPS.
    'bot_url' => env('WHATSAPP_BOT_URL', 'http://127.0.0.1:3001'),

    // Token compartido entre Laravel y el bot, para autenticar las llamadas en ambos sentidos.
    'bot_token' => env('WHATSAPP_BOT_TOKEN'),
];
