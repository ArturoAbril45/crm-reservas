@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'text-center ' . $class]) }}
     x-data="{
        hora: '',
        fecha: '',
        actualizar() {
            const ahora = new Date();
            this.hora = new Intl.DateTimeFormat('es-EC', { timeZone: 'America/Guayaquil', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }).format(ahora);
            const fechaTexto = new Intl.DateTimeFormat('es-EC', { timeZone: 'America/Guayaquil', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(ahora);
            this.fecha = fechaTexto.charAt(0).toUpperCase() + fechaTexto.slice(1);
        },
     }"
     x-init="actualizar(); setInterval(() => actualizar(), 1000)">
    <p class="text-lg font-semibold text-gray-900 tabular-nums" x-text="hora"></p>
    <p class="text-[11px] text-gray-400" x-text="fecha"></p>
</div>
