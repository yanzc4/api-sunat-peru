<?php

declare(strict_types=1);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host;
$cssPath = dirname(__DIR__, 2) . '/public/assets/css/landing.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Documentación pública de la API de Facturación Electrónica SUNAT.">
    <meta name="theme-color" content="#070707">
    <title>Documentación — Factura API</title>
    <script>
        (() => {
            const saved = localStorage.getItem('landing-theme');
            const dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&amp;family=Syne:wght@600;700;800&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/landing.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="grain landing-grid min-h-screen bg-paper font-body text-ink antialiased transition-colors duration-300 dark:bg-[#070707] dark:text-white">
    <header class="fixed inset-x-0 top-0 z-40 flex h-16 items-center justify-between border-b border-black/10 bg-paper/90 px-4 backdrop-blur-xl dark:border-white/10 dark:bg-[#070707]/90 lg:hidden">
        <a href="/" class="flex items-center gap-3" aria-label="Volver al inicio">
            <span class="grid size-9 rotate-3 place-items-center bg-ink text-xs font-black text-signal dark:bg-signal dark:text-black">F/</span>
            <span class="font-display text-sm font-extrabold">DOCS</span>
        </a>
        <div class="flex items-center gap-2">
            <button type="button" data-theme-toggle class="grid size-10 place-items-center border border-black/15 bg-white/50 dark:border-white/15 dark:bg-white/5" aria-label="Cambiar tema" aria-pressed="false">
                <svg class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
                <svg class="size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.6 6.6 0 0 0 21 12.8Z"/></svg>
            </button>
            <button id="docs-menu-toggle" type="button" class="grid size-10 place-items-center border border-black/15 dark:border-white/15" aria-label="Abrir índice" aria-expanded="false" aria-controls="docs-sidebar">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </header>

    <div id="docs-overlay" class="fixed inset-0 z-40 hidden bg-black/60 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

    <aside id="docs-sidebar" class="docs-scrollbar fixed inset-y-0 left-0 z-50 flex w-[19rem] -translate-x-full flex-col overflow-y-auto border-r border-white/10 bg-[#07111f] text-white transition-transform duration-300 dark:bg-[#050505] lg:translate-x-0">
        <div class="border-b border-white/10 p-6">
            <div class="flex items-center justify-between">
                <a href="/" class="group flex items-center gap-3" aria-label="Factura API, inicio">
                    <span class="grid size-10 rotate-3 place-items-center bg-signal text-sm font-black text-black transition group-hover:rotate-0">F/</span>
                    <span class="font-display text-lg font-extrabold tracking-[-.04em]">FACTURA<span class="text-signal">API</span></span>
                </a>
                <button id="docs-menu-close" type="button" class="grid size-9 place-items-center border border-white/15 lg:hidden" aria-label="Cerrar índice">×</button>
            </div>
            <div class="mt-7 flex items-center justify-between">
                <div><p class="font-mono text-[10px] font-bold uppercase tracking-[.2em] text-white/40">Referencia pública</p><p class="mt-1 text-sm font-bold">API v1</p></div>
                <span class="flex items-center gap-2 border border-signal/30 bg-signal/10 px-2.5 py-1.5 font-mono text-[10px] font-bold text-signal"><i class="size-1.5 rounded-full bg-signal not-italic"></i> ONLINE</span>
            </div>
        </div>

        <nav class="flex-1 p-4" aria-label="Índice de documentación">
            <p class="px-3 pb-2 pt-2 font-mono text-[10px] font-bold uppercase tracking-[.2em] text-white/30">Empezar</p>
            <a data-nav-link href="#autenticacion" class="docs-nav-link block border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white">Autenticación</a>
            <a data-nav-link href="#flujo" class="docs-nav-link block border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white">Flujo de emisión</a>
            <a data-nav-link href="#errores" class="docs-nav-link block border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white">Errores</a>

            <p class="mt-6 px-3 pb-2 pt-2 font-mono text-[10px] font-bold uppercase tracking-[.2em] text-white/30">Comprobantes</p>
            <a data-nav-link href="#listar" class="docs-nav-link flex items-center gap-3 border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white"><span class="font-mono text-[9px] text-signal">GET</span> Listar</a>
            <a data-nav-link href="#detalle" class="docs-nav-link flex items-center gap-3 border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white"><span class="font-mono text-[9px] text-signal">GET</span> Ver comprobante</a>
            <a data-nav-link href="#emitir" class="docs-nav-link flex items-center gap-3 border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white"><span class="font-mono text-[9px] text-[#7dd3fc]">POST</span> Emitir</a>
            <a data-nav-link href="#procesar" class="docs-nav-link flex items-center gap-3 border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white"><span class="font-mono text-[9px] text-[#7dd3fc]">POST</span> Procesar</a>
            <a data-nav-link href="#estado" class="docs-nav-link flex items-center gap-3 border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white"><span class="font-mono text-[9px] text-signal">GET</span> Consultar estado</a>
            <a data-nav-link href="#pdf" class="docs-nav-link flex items-center gap-3 border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white"><span class="font-mono text-[9px] text-signal">GET</span> Descargar PDF</a>
            <a data-nav-link href="#xml" class="docs-nav-link flex items-center gap-3 border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white"><span class="font-mono text-[9px] text-signal">GET</span> Descargar XML</a>
            <a data-nav-link href="#cdr" class="docs-nav-link flex items-center gap-3 border-l-2 border-transparent px-3 py-2.5 text-sm font-semibold text-white/60 transition hover:border-white/30 hover:bg-white/5 hover:text-white"><span class="font-mono text-[9px] text-signal">GET</span> Descargar CDR</a>
        </nav>

        <div class="border-t border-white/10 p-4">
            <a href="/" class="flex items-center justify-between border border-white/15 px-4 py-3 text-xs font-bold uppercase tracking-[.12em] text-white/70 transition hover:border-signal hover:text-signal">Volver al inicio <span>↗</span></a>
            <button type="button" data-theme-toggle class="mt-2 flex w-full items-center justify-between border border-white/15 px-4 py-3 text-xs font-bold uppercase tracking-[.12em] text-white/70 transition hover:border-signal hover:text-signal" aria-label="Cambiar tema" aria-pressed="false"><span>Tema visual</span><span data-theme-name>Automático</span></button>
        </div>
    </aside>

    <main class="docs-content min-w-0 pt-16 lg:pl-[19rem] lg:pt-0">
        <div class="mx-auto max-w-6xl px-5 py-12 sm:px-8 lg:px-12 lg:py-20">
            <section class="relative overflow-hidden border-b border-black/10 pb-14 dark:border-white/10">
                <div class="pointer-events-none absolute -right-24 -top-24 size-80 rounded-full bg-[#dce900]/25 blur-3xl dark:bg-signal/10"></div>
                <div class="relative">
                    <div class="mb-6 inline-flex items-center gap-3 border border-black/15 bg-white/55 px-3 py-2 font-mono text-[10px] font-bold uppercase tracking-[.18em] dark:border-white/15 dark:bg-white/5"><span class="size-2 rounded-full bg-[#718000] dark:bg-signal"></span> Documentación pública</div>
                    <h1 class="max-w-4xl font-display text-[1.85rem] font-extrabold uppercase leading-[.92] tracking-[-.06em] min-[420px]:text-[2.15rem] sm:text-7xl sm:leading-[.9]">API de facturación<br><span class="text-[#657500] dark:text-signal">electrónica.</span></h1>
                    <p class="mt-7 max-w-2xl text-lg leading-8 text-black/60 dark:text-white/55">Interfaz pública para emitir y consultar comprobantes de una empresa registrada.</p>
                    <div class="mt-8 flex flex-wrap gap-2 font-mono text-[10px] font-bold uppercase tracking-[.12em]"><span class="border border-black/15 px-3 py-2 dark:border-white/15">REST + JSON</span><span class="border border-black/15 px-3 py-2 dark:border-white/15">Token empresarial</span><span class="border border-black/15 px-3 py-2 dark:border-white/15">SUNAT Perú</span></div>
                </div>
            </section>

            <section id="autenticacion" data-doc-section class="docs-section grid gap-8 border-b border-black/10 py-16 dark:border-white/10 lg:grid-cols-[.4fr_1fr]">
                <div><p class="font-mono text-[10px] font-bold uppercase tracking-[.2em] text-[#657500] dark:text-signal">00 / Acceso</p><h2 class="mt-3 font-display text-3xl font-extrabold tracking-[-.04em]">Autenticación empresarial</h2></div>
                <div>
                    <div class="border-l-4 border-signal bg-ink p-5 text-sm leading-7 text-white dark:bg-signal dark:text-black">El token identifica automáticamente a la empresa. No envíes <code>empresa_id</code>: cualquier valor recibido será reemplazado por la empresa asociada al token.</div>
                    <ul class="mt-7 grid gap-3 text-sm leading-6 text-black/65 dark:text-white/60">
                        <li class="flex gap-3"><span class="font-mono font-bold text-[#657500] dark:text-signal">01</span><span>GET: <code>?token=TU_TOKEN</code>.</span></li>
                        <li class="flex gap-3"><span class="font-mono font-bold text-[#657500] dark:text-signal">02</span><span>POST: campo <code>"token": "TU_TOKEN"</code> en el JSON.</span></li>
                        <li class="flex gap-3"><span class="font-mono font-bold text-[#657500] dark:text-signal">03</span><span>JWT y cookies del dashboard no autentican esta API.</span></li>
                        <li class="flex gap-3"><span class="font-mono font-bold text-[#657500] dark:text-signal">04</span><span>Un token solo accede a comprobantes de su propia empresa.</span></li>
                        <li class="flex gap-3"><span class="font-mono font-bold text-[#657500] dark:text-signal">05</span><span>El destino SUNAT se toma de <code>empresas_facturacion.entorno</code>: <code>beta</code> usa el servicio de pruebas y <code>produccion</code> usa los servicios productivos.</span></li>
                    </ul>
                </div>
            </section>

            <section id="flujo" data-doc-section class="docs-section grid gap-8 border-b border-black/10 py-16 dark:border-white/10 lg:grid-cols-[.4fr_1fr]">
                <div><p class="font-mono text-[10px] font-bold uppercase tracking-[.2em] text-[#657500] dark:text-signal">01 / Operación</p><h2 class="mt-3 font-display text-3xl font-extrabold tracking-[-.04em]">Flujo de emisión</h2></div>
                <ol class="grid gap-3">
                    <li class="grid gap-3 border border-black/10 bg-white/45 p-5 dark:border-white/10 dark:bg-white/[.025] sm:grid-cols-[2.5rem_1fr]"><span class="grid size-10 place-items-center bg-ink font-display font-bold text-signal dark:bg-signal dark:text-black">1</span><p class="self-center text-sm leading-6"><code>POST /comprobantes</code> reserva el correlativo y crea un registro <code>pendiente</code>.</p></li>
                    <li class="grid gap-3 border border-black/10 bg-white/45 p-5 dark:border-white/10 dark:bg-white/[.025] sm:grid-cols-[2.5rem_1fr]"><span class="grid size-10 place-items-center bg-ink font-display font-bold text-signal dark:bg-signal dark:text-black">2</span><p class="self-center text-sm leading-6"><code>POST /comprobantes/{id}/procesar</code> genera XML, firma, envía a SUNAT y genera los PDF.</p></li>
                </ol>
            </section>

            <section id="errores" data-doc-section class="docs-section border-b border-black/10 py-16 dark:border-white/10">
                <div class="mb-8 flex items-end justify-between gap-5"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[.2em] text-[#657500] dark:text-signal">02 / Respuestas</p><h2 class="mt-3 font-display text-3xl font-extrabold tracking-[-.04em]">Errores</h2></div><span class="hidden font-mono text-[10px] uppercase tracking-[.16em] text-black/35 dark:text-white/30 sm:block">application/json</span></div>
                <pre class="docs-code">{
  "success": false,
  "error": { "code": "UNAUTHORIZED", "message": "Token empresarial inválido o inactivo" }
}</pre>
                <div class="mt-6 overflow-x-auto border border-black/10 dark:border-white/10">
                    <table class="w-full min-w-[42rem] border-collapse text-left text-sm">
                        <thead class="bg-ink text-white dark:bg-signal dark:text-black"><tr><th class="px-5 py-4 font-mono text-xs uppercase tracking-wider">HTTP</th><th class="px-5 py-4 font-mono text-xs uppercase tracking-wider">Código</th><th class="px-5 py-4 font-mono text-xs uppercase tracking-wider">Significado</th></tr></thead>
                        <tbody class="divide-y divide-black/10 bg-white/45 dark:divide-white/10 dark:bg-white/[.025]">
                            <tr><td class="px-5 py-4 font-mono font-bold">400</td><td class="px-5 py-4 font-mono text-xs">VALIDATION_ERROR</td><td class="px-5 py-4 text-black/60 dark:text-white/55">JSON, campos o estado no válidos.</td></tr>
                            <tr><td class="px-5 py-4 font-mono font-bold">401</td><td class="px-5 py-4 font-mono text-xs">UNAUTHORIZED</td><td class="px-5 py-4 text-black/60 dark:text-white/55">Token ausente, inválido o inactivo.</td></tr>
                            <tr><td class="px-5 py-4 font-mono font-bold">403</td><td class="px-5 py-4 font-mono text-xs">ACCOUNT_SUSPENDED</td><td class="px-5 py-4 text-black/60 dark:text-white/55">La empresa está suspendida y no puede usar ningún endpoint de comprobantes.</td></tr>
                            <tr><td class="px-5 py-4 font-mono font-bold">404</td><td class="px-5 py-4 font-mono text-xs">NOT_FOUND</td><td class="px-5 py-4 text-black/60 dark:text-white/55">Comprobante inexistente/ajeno o archivo no disponible.</td></tr>
                            <tr><td class="px-5 py-4 font-mono font-bold">422</td><td class="px-5 py-4 font-mono text-xs">SUNAT_ERROR</td><td class="px-5 py-4 text-black/60 dark:text-white/55">Error o rechazo devuelto por SUNAT.</td></tr>
                            <tr><td class="px-5 py-4 font-mono font-bold">500</td><td class="px-5 py-4 font-mono text-xs">INTERNAL_ERROR</td><td class="px-5 py-4 text-black/60 dark:text-white/55">Error interno sin detalles técnicos.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="py-16">
                <div class="mb-12"><p class="font-mono text-[10px] font-bold uppercase tracking-[.2em] text-[#657500] dark:text-signal">03 / Referencia</p><h2 class="mt-3 font-display text-4xl font-extrabold uppercase tracking-[-.05em] sm:text-5xl">Endpoints públicos</h2></div>

                <section id="listar" data-doc-section class="docs-section mb-6 border border-black/10 bg-white/50 dark:border-white/10 dark:bg-white/[.025]">
                    <div class="flex flex-col gap-3 border-b border-black/10 p-5 dark:border-white/10 sm:flex-row sm:items-center"><span class="w-fit bg-ink px-3 py-1.5 font-mono text-[10px] font-black tracking-wider text-signal dark:bg-signal dark:text-black">GET</span><code class="break-all font-mono text-sm font-bold">/api/facturacion/comprobantes</code><span class="sm:ml-auto font-mono text-[10px] text-black/35 dark:text-white/30">#01</span></div>
                    <div class="p-5 sm:p-7"><p class="text-sm leading-7 text-black/65 dark:text-white/60">Lista comprobantes propios. Filtros opcionales: <code>estado</code> y <code>tipo_comprobante</code>.</p><pre class="docs-code">GET <?= htmlspecialchars($baseUrl) ?>/api/facturacion/comprobantes?token=TU_TOKEN&amp;estado=aceptado&amp;tipo_comprobante=03</pre><p class="mt-5 text-sm"><strong>200:</strong> <code>{"success":true,"data":[...]}</code></p></div>
                </section>

                <section id="detalle" data-doc-section class="docs-section mb-6 border border-black/10 bg-white/50 dark:border-white/10 dark:bg-white/[.025]">
                    <div class="flex flex-col gap-3 border-b border-black/10 p-5 dark:border-white/10 sm:flex-row sm:items-center"><span class="w-fit bg-ink px-3 py-1.5 font-mono text-[10px] font-black tracking-wider text-signal dark:bg-signal dark:text-black">GET</span><code class="break-all font-mono text-sm font-bold">/api/facturacion/comprobantes/{id}</code><span class="sm:ml-auto font-mono text-[10px] text-black/35 dark:text-white/30">#02</span></div>
                    <div class="p-5 sm:p-7"><p class="text-sm leading-7 text-black/65 dark:text-white/60">Devuelve datos, detalles y URLs de descarga de un comprobante propio.</p><pre class="docs-code">GET <?= htmlspecialchars($baseUrl) ?>/api/facturacion/comprobantes/12?token=TU_TOKEN</pre><p class="mt-5 text-sm"><strong>200:</strong> objeto del comprobante. <strong>404:</strong> ID inexistente o ajeno.</p></div>
                </section>

                <section id="emitir" data-doc-section class="docs-section mb-6 border border-black/10 bg-white/50 dark:border-white/10 dark:bg-white/[.025]">
                    <div class="flex flex-col gap-3 border-b border-black/10 p-5 dark:border-white/10 sm:flex-row sm:items-center"><span class="w-fit bg-[#0ea5e9] px-3 py-1.5 font-mono text-[10px] font-black tracking-wider text-black">POST</span><code class="break-all font-mono text-sm font-bold">/api/facturacion/comprobantes</code><span class="sm:ml-auto font-mono text-[10px] text-black/35 dark:text-white/30">#03</span></div>
                    <div class="p-5 sm:p-7"><p class="text-sm leading-7 text-black/65 dark:text-white/60">Crea el comprobante y reserva su correlativo; todavía no lo envía a SUNAT.</p><pre class="docs-code">{
  "token": "TU_TOKEN",
  "tipo_comprobante": "03",
  "serie": "B001",
  "moneda": "PEN",
  "fecha_emision": "2026-09-16",
  "cliente": {
    "tipo_documento": "1", "numero_documento": "12345678",
    "nombre": "Juan Perez", "direccion": "Av. Lima 123"
  },
  "items": [{
    "codigo": "P001", "descripcion": "Producto de prueba", "unidad": "NIU",
    "cantidad": 1, "precio_unitario": 118.00, "afectacion_igv": "10"
  }]
}</pre><pre class="docs-code">{
  "success": true,
  "data": {
    "id": 12, "tipo": "03", "serie": "B001", "correlativo": "00000012",
    "numero": "B001-00000012", "estado": "pendiente", "entorno": "beta",
    "message": "Comprobante creado. Usar POST /procesar para enviar a SUNAT."
  }
}</pre><p class="mt-5 text-sm"><strong>201 Created.</strong> Tipos: 01, 03, 07 y 08. Monedas: PEN y USD.</p></div>
                </section>

                <section id="procesar" data-doc-section class="docs-section mb-6 border border-black/10 bg-white/50 dark:border-white/10 dark:bg-white/[.025]">
                    <div class="flex flex-col gap-3 border-b border-black/10 p-5 dark:border-white/10 sm:flex-row sm:items-center"><span class="w-fit bg-[#0ea5e9] px-3 py-1.5 font-mono text-[10px] font-black tracking-wider text-black">POST</span><code class="break-all font-mono text-sm font-bold">/api/facturacion/comprobantes/{id}/procesar</code><span class="sm:ml-auto font-mono text-[10px] text-black/35 dark:text-white/30">#04</span></div>
                    <div class="p-5 sm:p-7"><p class="text-sm leading-7 text-black/65 dark:text-white/60">Genera y firma XML, envía a SUNAT y genera PDF A4 y ticket. Estados admitidos: <code>pendiente</code>, <code>generando</code> y <code>error</code>.</p><pre class="docs-code">{ "token": "TU_TOKEN" }</pre><p class="mt-5 text-sm"><strong>200:</strong> estado, entorno usado, hash, rutas y mensaje SUNAT. <strong>400:</strong> estado no procesable. <strong>404:</strong> ID inexistente o ajeno.</p></div>
                </section>

                <section id="estado" data-doc-section class="docs-section mb-6 border border-black/10 bg-white/50 dark:border-white/10 dark:bg-white/[.025]">
                    <div class="flex flex-col gap-3 border-b border-black/10 p-5 dark:border-white/10 sm:flex-row sm:items-center"><span class="w-fit bg-ink px-3 py-1.5 font-mono text-[10px] font-black tracking-wider text-signal dark:bg-signal dark:text-black">GET</span><code class="break-all font-mono text-sm font-bold">/api/facturacion/comprobantes/{id}/estado</code><span class="sm:ml-auto font-mono text-[10px] text-black/35 dark:text-white/30">#05</span></div>
                    <div class="p-5 sm:p-7"><p class="text-sm leading-7 text-black/65 dark:text-white/60">En producción consulta el servicio de estado de SUNAT. En beta devuelve el estado confirmado durante el envío y el CDR almacenado, porque SUNAT no ofrece un servicio beta de consulta de CDR.</p><pre class="docs-code">GET <?= htmlspecialchars($baseUrl) ?>/api/facturacion/comprobantes/12/estado?token=TU_TOKEN</pre><p class="mt-5 text-sm"><strong>200:</strong> respuesta normalizada; en beta incluye <code>source=local_cdr</code> y <code>cdr_disponible</code>. <strong>422:</strong> error o rechazo SUNAT en producción.</p></div>
                </section>

                <section id="pdf" data-doc-section class="docs-section mb-6 border border-black/10 bg-white/50 dark:border-white/10 dark:bg-white/[.025]">
                    <div class="flex flex-col gap-3 border-b border-black/10 p-5 dark:border-white/10 sm:flex-row sm:items-center"><span class="w-fit bg-ink px-3 py-1.5 font-mono text-[10px] font-black tracking-wider text-signal dark:bg-signal dark:text-black">GET</span><code class="break-all font-mono text-sm font-bold">/api/facturacion/comprobantes/{id}/pdf</code><span class="sm:ml-auto font-mono text-[10px] text-black/35 dark:text-white/30">#06</span></div>
                    <div class="p-5 sm:p-7"><p class="text-sm leading-7 text-black/65 dark:text-white/60">Devuelve <code>application/pdf</code>. Usa A4 por defecto o <code>formato=ticket</code>.</p><pre class="docs-code">GET <?= htmlspecialchars($baseUrl) ?>/api/facturacion/comprobantes/12/pdf?token=TU_TOKEN&amp;formato=ticket
GET <?= htmlspecialchars($baseUrl) ?>/api/facturacion/comprobantes/12/pdf?token=TU_TOKEN&amp;formato=ticket&amp;disposicion=inline</pre><p class="mt-5 text-sm leading-7"><code>disposicion=attachment</code> fuerza la descarga y es el valor predeterminado. <code>disposicion=inline</code> permite mostrar el PDF en una pestaña, visor o <code>iframe</code>.</p><p class="mt-3 text-sm"><strong>200:</strong> contenido binario del PDF. <strong>400:</strong> disposición inválida. <strong>404:</strong> comprobante o formato no disponible.</p></div>
                </section>

                <section id="xml" data-doc-section class="docs-section mb-6 border border-black/10 bg-white/50 dark:border-white/10 dark:bg-white/[.025]">
                    <div class="flex flex-col gap-3 border-b border-black/10 p-5 dark:border-white/10 sm:flex-row sm:items-center"><span class="w-fit bg-ink px-3 py-1.5 font-mono text-[10px] font-black tracking-wider text-signal dark:bg-signal dark:text-black">GET</span><code class="break-all font-mono text-sm font-bold">/api/facturacion/comprobantes/{id}/xml</code><span class="sm:ml-auto font-mono text-[10px] text-black/35 dark:text-white/30">#07</span></div>
                    <div class="p-5 sm:p-7"><p class="text-sm leading-7 text-black/65 dark:text-white/60">Descarga el XML firmado como <code>application/xml</code>.</p><pre class="docs-code">GET <?= htmlspecialchars($baseUrl) ?>/api/facturacion/comprobantes/12/xml?token=TU_TOKEN</pre></div>
                </section>

                <section id="cdr" data-doc-section class="docs-section mb-6 border border-black/10 bg-white/50 dark:border-white/10 dark:bg-white/[.025]">
                    <div class="flex flex-col gap-3 border-b border-black/10 p-5 dark:border-white/10 sm:flex-row sm:items-center"><span class="w-fit bg-ink px-3 py-1.5 font-mono text-[10px] font-black tracking-wider text-signal dark:bg-signal dark:text-black">GET</span><code class="break-all font-mono text-sm font-bold">/api/facturacion/comprobantes/{id}/cdr</code><span class="sm:ml-auto font-mono text-[10px] text-black/35 dark:text-white/30">#08</span></div>
                    <div class="p-5 sm:p-7"><p class="text-sm leading-7 text-black/65 dark:text-white/60">Descarga la constancia CDR como <code>application/zip</code>.</p><pre class="docs-code">GET <?= htmlspecialchars($baseUrl) ?>/api/facturacion/comprobantes/12/cdr?token=TU_TOKEN</pre></div>
                </section>
            </div>

            <div class="border border-black/10 bg-ink p-6 text-sm leading-7 text-white dark:border-signal/20 dark:bg-signal dark:text-black"><span class="mr-3 font-mono font-black text-signal dark:text-black">NOTA</span> Login, dashboard y administración de empresas son interfaces internas y no forman parte de esta API pública.</div>

            <footer class="flex flex-col justify-between gap-4 py-10 text-xs text-black/40 dark:text-white/35 sm:flex-row"><p>Factura API · Documentación pública</p><a href="/" class="font-bold uppercase tracking-wider hover:text-black dark:hover:text-white">Volver al inicio ↗</a></footer>
        </div>
    </main>

    <script>
        const root = document.documentElement;
        const themeButtons = document.querySelectorAll('[data-theme-toggle]');
        const themeNames = document.querySelectorAll('[data-theme-name]');

        function syncTheme() {
            const isDark = root.classList.contains('dark');
            themeButtons.forEach((button) => {
                button.setAttribute('aria-pressed', String(isDark));
                button.setAttribute('aria-label', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            });
            themeNames.forEach((name) => { name.textContent = isDark ? 'Oscuro' : 'Claro'; });
        }

        themeButtons.forEach((button) => button.addEventListener('click', () => {
            const isDark = root.classList.toggle('dark');
            localStorage.setItem('landing-theme', isDark ? 'dark' : 'light');
            syncTheme();
        }));
        syncTheme();

        const sidebar = document.getElementById('docs-sidebar');
        const overlay = document.getElementById('docs-overlay');
        const menuToggle = document.getElementById('docs-menu-toggle');
        const menuClose = document.getElementById('docs-menu-close');

        function setMenu(open) {
            sidebar.classList.toggle('-translate-x-full', !open);
            overlay.classList.toggle('hidden', !open);
            menuToggle?.setAttribute('aria-expanded', String(open));
            document.body.classList.toggle('overflow-hidden', open && window.innerWidth < 1024);
        }

        menuToggle?.addEventListener('click', () => setMenu(true));
        menuClose?.addEventListener('click', () => setMenu(false));
        overlay?.addEventListener('click', () => setMenu(false));
        sidebar.querySelectorAll('a[href^="#"]').forEach((link) => link.addEventListener('click', () => setMenu(false)));

        const navLinks = [...document.querySelectorAll('[data-nav-link]')];
        const sections = document.querySelectorAll('[data-doc-section]');
        const observer = new IntersectionObserver((entries) => {
            const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
            if (!visible) return;
            navLinks.forEach((link) => link.classList.toggle('is-active', link.getAttribute('href') === '#' + visible.target.id));
        }, { rootMargin: '-15% 0px -70% 0px', threshold: [0, .15, .35] });
        sections.forEach((section) => observer.observe(section));
    </script>
</body>
</html>
