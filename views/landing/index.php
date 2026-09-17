<?php

declare(strict_types=1);

$whatsappUrl = 'https://wa.me/51979829261?text=' . rawurlencode(
    'Hola, quiero información sobre la API de Facturación Electrónica SUNAT.'
);
$cssPath = dirname(__DIR__, 2) . '/public/assets/css/landing.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="API de facturación electrónica SUNAT para emitir, procesar y consultar comprobantes desde cualquier sistema.">
    <meta name="theme-color" content="#070707">
    <title>Factura API — Facturación electrónica sin fricción</title>
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
<body class="grain landing-grid min-h-screen overflow-x-hidden bg-paper text-ink antialiased transition-colors duration-300 dark:bg-[#070707] dark:text-white">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-black/10 bg-[#f4f1e8]/85 backdrop-blur-xl dark:border-white/10 dark:bg-[#070707]/80">
        <nav class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8" aria-label="Navegación principal">
            <a href="/" class="group flex items-center gap-3" aria-label="Factura API, inicio">
                <span class="grid size-10 rotate-3 place-items-center bg-ink text-sm font-black text-signal transition-transform group-hover:rotate-0 dark:bg-signal dark:text-black">F/</span>
                <span class="hidden font-display text-lg font-extrabold tracking-[-0.04em] sm:inline">FACTURA<span class="text-[#657500] dark:text-signal">API</span></span>
            </a>

            <div class="hidden items-center gap-8 text-sm font-semibold lg:flex">
                <a href="#producto" class="transition hover:text-[#657500] dark:hover:text-signal">Producto</a>
                <a href="#como-funciona" class="transition hover:text-[#657500] dark:hover:text-signal">Cómo funciona</a>
                <a href="#planes" class="transition hover:text-[#657500] dark:hover:text-signal">Planes</a>
                <a href="/doc" class="transition hover:text-[#657500] dark:hover:text-signal">Documentación</a>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" data-theme-toggle class="grid size-10 place-items-center border border-black/15 bg-white/50 transition hover:-translate-y-0.5 hover:border-black dark:border-white/15 dark:bg-white/5 dark:hover:border-signal" aria-label="Cambiar tema" aria-pressed="false">
                    <svg data-theme-sun class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
                    <svg data-theme-moon class="size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.6 6.6 0 0 0 21 12.8Z"/></svg>
                </button>
                <a href="/login" class="hidden border border-ink px-4 py-2.5 text-sm font-bold transition hover:bg-ink hover:text-white sm:inline-flex dark:border-white/20 dark:hover:border-white dark:hover:bg-white dark:hover:text-black">Ingresar</a>
                <a href="/contactar" class="hidden items-center gap-2 bg-ink px-4 py-2.5 text-sm font-extrabold text-white shadow-[4px_4px_0_#eaff00] transition hover:-translate-y-0.5 hover:shadow-[6px_6px_0_#eaff00] dark:bg-signal dark:text-black dark:shadow-[4px_4px_0_#ffffff] sm:inline-flex">Empezar <span aria-hidden="true">↗</span></a>
                <button id="menu-toggle" type="button" class="grid size-10 place-items-center lg:hidden" aria-label="Abrir menú" aria-expanded="false" aria-controls="mobile-menu">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </nav>
        <div id="mobile-menu" class="hidden border-t border-black/10 bg-paper px-5 py-5 dark:border-white/10 dark:bg-[#070707] lg:hidden">
            <div class="flex flex-col gap-4 font-semibold">
                <a href="#producto">Producto</a><a href="#como-funciona">Cómo funciona</a><a href="#planes">Planes</a><a href="/doc">Documentación</a><a href="/login">Iniciar sesión</a>
            </div>
        </div>
    </header>

    <main>
        <section class="relative isolate overflow-hidden pb-20 pt-36 sm:pt-44 lg:pb-28 lg:pt-52">
            <div class="pointer-events-none absolute -right-20 top-24 -z-10 size-72 rounded-full bg-[#d9e700]/20 blur-3xl dark:bg-signal/10 lg:size-[34rem]"></div>
            <div class="mx-auto grid max-w-7xl items-center gap-16 px-5 lg:grid-cols-[1.08fr_.92fr] lg:px-8">
                <div>
                    <div class="reveal mb-7 inline-flex items-center gap-3 border border-black/15 bg-white/60 px-3 py-2 text-xs font-bold uppercase tracking-[.18em] dark:border-white/15 dark:bg-white/5" style="animation-delay:80ms">
                        <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-[#728000] opacity-70 dark:bg-signal"></span><span class="relative inline-flex size-2 rounded-full bg-[#728000] dark:bg-signal"></span></span>
                        Conectado con SUNAT · Perú
                    </div>
                    <h1 class="reveal max-w-4xl font-display text-[2.5rem] font-extrabold uppercase leading-[.86] tracking-[-.075em] min-[420px]:text-[3rem] sm:text-[clamp(3.5rem,8vw,7.5rem)] sm:leading-[.82]" style="animation-delay:150ms">Factura.<br><span class="relative inline-block text-[#657500] dark:text-signal">Integra.</span><br>Escala.</h1>
                    <p class="reveal mt-8 max-w-xl text-lg leading-8 text-black/65 dark:text-white/60" style="animation-delay:240ms">Una API directa para emitir comprobantes electrónicos, firmarlos y enviarlos a SUNAT. Tu sistema envía JSON; nosotros resolvemos el resto.</p>
                    <div class="reveal mt-9 flex flex-col gap-3 sm:flex-row" style="animation-delay:320ms">
                        <a href="/contactar" class="group inline-flex items-center justify-center gap-3 bg-ink px-7 py-4 text-sm font-extrabold uppercase tracking-[.08em] text-white transition hover:-translate-y-1 dark:bg-signal dark:text-black">Solicitar acceso <span class="transition-transform group-hover:translate-x-1" aria-hidden="true">→</span></a>
                        <a href="/doc" class="inline-flex items-center justify-center gap-3 border border-black/20 bg-white/45 px-7 py-4 text-sm font-extrabold uppercase tracking-[.08em] transition hover:border-black hover:bg-white dark:border-white/20 dark:bg-white/5 dark:border-white/20 dark:hover:border-white dark:hover:bg-white dark:hover:text-black">Explorar la API</a>
                    </div>
                    <div class="reveal mt-10 flex flex-wrap gap-x-7 gap-y-3 text-xs font-bold uppercase tracking-[.12em] text-black/50 dark:text-white/45" style="animation-delay:390ms">
                        <span class="flex items-center gap-2"><b class="text-[#728000] dark:text-signal">✓</b> Multiempresa</span><span class="flex items-center gap-2"><b class="text-[#728000] dark:text-signal">✓</b> Beta y producción</span><span class="flex items-center gap-2"><b class="text-[#728000] dark:text-signal">✓</b> PDF + XML + CDR</span>
                    </div>
                </div>

                <div class="reveal relative lg:pl-8" style="animation-delay:260ms">
                    <div class="absolute -left-3 -top-5 z-10 bg-signal px-4 py-2 font-mono text-xs font-black uppercase tracking-widest text-black shadow-[4px_4px_0_#07111f] dark:shadow-[4px_4px_0_#fff]">Respuesta 201</div>
                    <div class="border border-black/15 bg-[#fffdf7] p-2 shadow-[18px_18px_0_rgba(7,17,31,.12)] dark:border-white/15 dark:bg-[#101010] dark:shadow-[18px_18px_0_rgba(234,255,0,.11)]">
                        <div class="flex items-center justify-between border-b border-black/10 px-4 py-3 dark:border-white/10"><div class="flex gap-1.5"><span class="size-2.5 rounded-full bg-[#ff6b57]"></span><span class="size-2.5 rounded-full bg-[#ffd05b]"></span><span class="size-2.5 rounded-full bg-[#5ad08a]"></span></div><span class="font-mono text-[10px] uppercase tracking-[.16em] text-black/40 dark:text-white/40">POST /comprobantes</span></div>
                        <div class="grid gap-4 p-4 sm:p-6">
                            <div class="flex items-center justify-between border border-black/10 bg-[#f4f1e8] px-4 py-3 dark:border-white/10 dark:bg-black"><span class="font-mono text-xs text-black/50 dark:text-white/50">Estado de operación</span><span class="flex items-center gap-2 font-mono text-xs font-bold"><i class="size-2 rounded-full bg-[#6f7c00] not-italic dark:bg-signal"></i> ACEPTADO</span></div>
                            <pre class="whitespace-pre-wrap break-words bg-ink p-5 font-mono text-[10px] leading-6 text-white dark:bg-[#050505]"><code><span class="text-signal">{</span>
  <span class="text-[#9fd5ff]">"success"</span>: <span class="text-signal">true</span>,
  <span class="text-[#9fd5ff]">"data"</span>: <span class="text-signal">{</span>
    <span class="text-[#9fd5ff]">"numero"</span>: <span class="text-[#ffd98e]">"F001-00001842"</span>,
    <span class="text-[#9fd5ff]">"entorno"</span>: <span class="text-[#ffd98e]">"produccion"</span>,
    <span class="text-[#9fd5ff]">"estado"</span>: <span class="text-[#ffd98e]">"aceptado"</span>,
    <span class="text-[#9fd5ff]">"cdr_disponible"</span>: <span class="text-signal">true</span>
  <span class="text-signal">}</span>
<span class="text-signal">}</span></code></pre>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="border border-black/10 p-3 dark:border-white/10"><span class="block text-[10px] uppercase tracking-widest text-black/45 dark:text-white/40">Archivos</span><strong class="mt-1 block font-display text-lg">03</strong></div>
                                <div class="border border-black/10 p-3 dark:border-white/10"><span class="block text-[10px] uppercase tracking-widest text-black/45 dark:text-white/40">Formato</span><strong class="mt-1 block font-display text-lg">JSON</strong></div>
                                <div class="border border-black/10 p-3 dark:border-white/10"><span class="block text-[10px] uppercase tracking-widest text-black/45 dark:text-white/40">Ámbito</span><strong class="mt-1 block font-display text-lg">1 RUC</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="overflow-hidden border-y border-black/10 bg-ink py-4 text-white dark:border-white/10 dark:bg-signal dark:text-black" aria-hidden="true"><div class="marquee-track flex w-max items-center"><?php for ($i = 0; $i < 2; $i++): ?><div class="flex shrink-0 items-center gap-7 pr-7 font-display text-sm font-extrabold uppercase tracking-[.14em]"><span>Boletas</span><span>✦</span><span>Facturas</span><span>✦</span><span>Notas de crédito</span><span>✦</span><span>PDF A4 + Ticket</span><span>✦</span><span>API multiempresa</span><span>✦</span></div><?php endfor; ?></div></div>

        <section id="producto" class="mx-auto max-w-7xl px-5 py-24 lg:px-8 lg:py-32">
            <div class="grid gap-12 lg:grid-cols-[.72fr_1.28fr] lg:gap-20">
                <div class="lg:sticky lg:top-28 lg:self-start"><p class="mb-5 font-mono text-xs font-bold uppercase tracking-[.22em] text-[#657500] dark:text-signal">01 / El producto</p><h2 class="font-display text-4xl font-extrabold uppercase leading-[.95] tracking-[-.05em] sm:text-6xl">Menos trámite.<br>Más producto.</h2><p class="mt-6 max-w-md leading-7 text-black/60 dark:text-white/55">Diseñada para equipos que quieren integrar facturación sin convertirse en expertos en XML, certificados o SOAP.</p></div>
                <div class="grid gap-px border border-black/10 bg-black/10 dark:border-white/10 dark:bg-white/10 sm:grid-cols-2">
                    <?php
                    $features = [
                        ['01', 'Un token, una empresa', 'Cada credencial queda aislada por RUC. Tu integración nunca necesita enviar empresa_id.'],
                        ['02', 'Flujo controlado', 'Crea primero, procesa después y consulta el estado cuando lo necesites.'],
                        ['03', 'Entorno por empresa', 'Configura beta o producción por compañía sin variables globales que mezclen operaciones.'],
                        ['04', 'Todo el expediente', 'Obtén PDF A4 o ticket, XML firmado y CDR desde endpoints simples.'],
                    ];
                    foreach ($features as [$number, $title, $copy]):
                    ?>
                        <article class="group min-h-64 bg-paper p-7 transition hover:bg-white dark:bg-[#070707] dark:hover:bg-[#101010]"><div class="flex items-start justify-between"><span class="font-mono text-xs text-black/40 dark:text-white/35"><?= $number ?></span><span class="grid size-8 place-items-center border border-black/15 text-sm transition group-hover:rotate-45 group-hover:bg-signal group-hover:text-black dark:border-white/15">↗</span></div><h3 class="mt-14 font-display text-2xl font-bold tracking-[-.035em]"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3><p class="mt-3 text-sm leading-6 text-black/55 dark:text-white/50"><?= htmlspecialchars($copy, ENT_QUOTES, 'UTF-8') ?></p></article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="como-funciona" class="border-y border-black/10 bg-white/55 py-24 dark:border-white/10 dark:bg-white/[.025] lg:py-32">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end"><div><p class="mb-5 font-mono text-xs font-bold uppercase tracking-[.22em] text-[#657500] dark:text-signal">02 / Cómo funciona</p><h2 class="max-w-2xl font-display text-4xl font-extrabold uppercase leading-[.95] tracking-[-.05em] sm:text-6xl">De JSON a SUNAT<br>en tres movimientos.</h2></div><a href="/doc" class="group inline-flex items-center gap-3 text-sm font-bold underline decoration-signal decoration-4 underline-offset-8">Ver documentación <span class="transition group-hover:translate-x-1">→</span></a></div>
                <div class="mt-16 grid gap-4 lg:grid-cols-3">
                    <?php
                    $steps = [
                        ['1', 'POST', 'Crea el comprobante', 'Envía cliente, items y serie. La API reserva el correlativo en el ámbito de tu empresa.'],
                        ['2', 'PROCESAR', 'Firma y envía', 'Generamos XML, aplicamos la firma digital y usamos el entorno SUNAT configurado.'],
                        ['3', 'GET', 'Consulta y entrega', 'Consulta estado y entrega PDF, XML o CDR desde tu propio sistema.'],
                    ];
                    foreach ($steps as [$number, $label, $title, $copy]):
                    ?>
                        <article class="relative overflow-hidden border border-black/15 p-7 dark:border-white/15"><span class="absolute -right-3 -top-8 font-display text-9xl font-black text-black/[.035] dark:text-white/[.035]"><?= $number ?></span><span class="font-mono text-xs font-bold text-[#657500] dark:text-signal"><?= $label ?></span><h3 class="mt-12 font-display text-2xl font-bold"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3><p class="mt-3 text-sm leading-6 text-black/55 dark:text-white/50"><?= htmlspecialchars($copy, ENT_QUOTES, 'UTF-8') ?></p></article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="planes" class="mx-auto max-w-7xl px-5 py-24 lg:px-8 lg:py-32">
            <div class="mx-auto max-w-3xl text-center"><p class="mb-5 font-mono text-xs font-bold uppercase tracking-[.22em] text-[#657500] dark:text-signal">03 / Planes referenciales</p><h2 class="font-display text-4xl font-extrabold uppercase leading-[.95] tracking-[-.05em] sm:text-6xl">Empieza pequeño.<br>Factura en grande.</h2><p class="mx-auto mt-6 max-w-xl text-black/60 dark:text-white/55">Opciones pensadas para integraciones nuevas, negocios en crecimiento y operaciones multiempresa.</p></div>
            <div class="mt-16 grid items-stretch gap-5 lg:grid-cols-3">
                <article class="flex flex-col border border-black/15 bg-white/40 p-7 dark:border-white/15 dark:bg-white/[.025]"><p class="font-mono text-xs font-bold uppercase tracking-[.18em] text-black/45 dark:text-white/45">Inicio</p><div class="mt-5 flex items-end gap-2"><span class="font-display text-5xl font-extrabold tracking-[-.06em]">S/ 79</span><span class="mb-2 text-sm text-black/45 dark:text-white/45">/ mes</span></div><p class="mt-4 text-sm leading-6 text-black/55 dark:text-white/50">Para una empresa que comienza a automatizar su facturación.</p><ul class="my-8 space-y-3 text-sm"><li>✓ 1 RUC</li><li>✓ 500 comprobantes/mes</li><li>✓ PDF A4 y ticket</li><li>✓ Soporte por correo</li></ul><a href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="mt-auto border border-ink px-5 py-3 text-center text-sm font-extrabold uppercase tracking-wider transition hover:bg-ink hover:text-white dark:border-white/25 dark:hover:bg-white dark:hover:text-black">Consultar</a></article>
                <article class="relative flex flex-col bg-ink p-7 text-white shadow-[12px_12px_0_#c9d900] dark:bg-signal dark:text-black dark:shadow-[12px_12px_0_#fff] lg:-translate-y-5"><span class="absolute right-5 top-5 bg-signal px-3 py-1 font-mono text-[10px] font-black uppercase tracking-widest text-black dark:bg-black dark:text-signal">Recomendado</span><p class="font-mono text-xs font-bold uppercase tracking-[.18em] text-white/55 dark:text-black/55">Premium</p><div class="mt-5 flex items-end gap-2"><span class="font-display text-5xl font-extrabold tracking-[-.06em]">S/ 329</span><span class="mb-2 text-sm text-white/50 dark:text-black/50">/ mes</span></div><p class="mt-4 text-sm leading-6 text-white/65 dark:text-black/65">Para plataformas, estudios contables y operaciones con varios RUC.</p><ul class="my-8 space-y-3 text-sm"><li>✓ Hasta 10 RUC</li><li>✓ 10,000 comprobantes/mes</li><li>✓ Prioridad de procesamiento</li><li>✓ Soporte técnico preferente</li><li>✓ Acompañamiento de integración</li></ul><a href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="mt-auto bg-signal px-5 py-3 text-center text-sm font-extrabold uppercase tracking-wider text-black transition hover:-translate-y-1 dark:bg-black dark:text-signal">Elegir Premium</a></article>
                <article class="flex flex-col border border-black/15 bg-white/40 p-7 dark:border-white/15 dark:bg-white/[.025]"><p class="font-mono text-xs font-bold uppercase tracking-[.18em] text-black/45 dark:text-white/45">Negocio</p><div class="mt-5 flex items-end gap-2"><span class="font-display text-5xl font-extrabold tracking-[-.06em]">S/ 169</span><span class="mb-2 text-sm text-black/45 dark:text-white/45">/ mes</span></div><p class="mt-4 text-sm leading-6 text-black/55 dark:text-white/50">Para equipos con mayor volumen y más de una empresa.</p><ul class="my-8 space-y-3 text-sm"><li>✓ Hasta 3 RUC</li><li>✓ 3,000 comprobantes/mes</li><li>✓ Todos los archivos</li><li>✓ Soporte por WhatsApp</li></ul><a href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="mt-auto border border-ink px-5 py-3 text-center text-sm font-extrabold uppercase tracking-wider transition hover:bg-ink hover:text-white dark:border-white/25 dark:hover:bg-white dark:hover:text-black">Consultar</a></article>
            </div>
            <p class="mt-8 text-center text-xs text-black/45 dark:text-white/40">Precios y límites referenciales. Escríbenos para recibir una propuesta según tu operación.</p>
        </section>

        <section class="mx-4 mb-5 overflow-hidden bg-ink text-white dark:bg-signal dark:text-black lg:mx-8"><div class="mx-auto grid max-w-7xl items-end gap-10 px-6 py-16 sm:px-10 lg:grid-cols-[1fr_auto] lg:px-12 lg:py-20"><div><p class="font-mono text-xs font-bold uppercase tracking-[.22em] text-signal dark:text-black/55">¿Listo para conectar?</p><h2 class="mt-5 max-w-4xl font-display text-4xl font-extrabold uppercase leading-[.92] tracking-[-.055em] sm:text-6xl">Tu próxima factura<br>empieza con una petición.</h2></div><a href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex min-w-56 items-center justify-between gap-5 bg-signal px-6 py-4 font-extrabold uppercase tracking-wider text-black transition hover:-translate-y-1 dark:bg-black dark:text-signal">Hablar ahora <span>↗</span></a></div></section>
    </main>

    <footer class="border-t border-black/10 px-5 py-10 dark:border-white/10 lg:px-8"><div class="mx-auto flex max-w-7xl flex-col justify-between gap-7 md:flex-row md:items-end"><div><a href="/" class="font-display text-xl font-extrabold tracking-[-.04em]">FACTURA<span class="text-[#657500] dark:text-signal">API</span></a><p class="mt-3 max-w-sm text-sm leading-6 text-black/50 dark:text-white/45">Infraestructura simple para facturación electrónica SUNAT.</p></div><div class="flex flex-col gap-3 text-sm md:items-end"><div class="flex flex-wrap gap-5 font-semibold"><a href="/doc" class="hover:underline">Documentación</a><a href="/login" class="hover:underline">Panel</a><a href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="hover:underline">WhatsApp</a></div><p class="text-black/45 dark:text-white/40">© <?= date('Y') ?> · Creado por <a href="https://codemultiall.net" target="_blank" rel="noopener noreferrer" class="font-bold text-ink underline decoration-signal decoration-2 underline-offset-4 dark:text-white">Codemultiall</a></p></div></div></footer>

    <a href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="fixed bottom-5 right-5 z-40 grid size-14 place-items-center rounded-full bg-[#25D366] text-white shadow-[0_10px_30px_rgba(0,0,0,.25)] transition hover:-translate-y-1 hover:scale-105" aria-label="Contactar por WhatsApp"><svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.8 11.8 0 0 0 12.08 0C5.5 0 .14 5.36.14 11.95c0 2.1.55 4.16 1.6 5.97L.04 24l6.22-1.63a11.92 11.92 0 0 0 5.81 1.48h.01C18.66 23.85 24 18.49 24 11.9c0-3.18-1.23-6.17-3.48-8.42ZM12.08 21.84h-.01a9.9 9.9 0 0 1-5.05-1.38l-.36-.21-3.69.97.99-3.6-.23-.37a9.93 9.93 0 1 1 8.35 4.59Zm5.45-7.44c-.3-.15-1.77-.87-2.04-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48a9 9 0 0 1-1.66-2.07c-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.77-.72 2.01-1.42.25-.7.25-1.31.18-1.44-.08-.12-.28-.2-.58-.35Z"/></svg></a>

    <script>
        const root = document.documentElement;
        const themeButtons = document.querySelectorAll('[data-theme-toggle]');
        function syncThemeButtons() {
            const isDark = root.classList.contains('dark');
            themeButtons.forEach((button) => {
                button.setAttribute('aria-pressed', String(isDark));
                button.setAttribute('aria-label', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            });
        }
        themeButtons.forEach((button) => button.addEventListener('click', () => {
            const isDark = root.classList.toggle('dark');
            localStorage.setItem('landing-theme', isDark ? 'dark' : 'light');
            syncThemeButtons();
        }));
        syncThemeButtons();

        const menuButton = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        menuButton?.addEventListener('click', () => {
            const isOpen = !mobileMenu.classList.toggle('hidden');
            menuButton.setAttribute('aria-expanded', String(isOpen));
        });
        mobileMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
            menuButton?.setAttribute('aria-expanded', 'false');
        }));
    </script>
</body>
</html>
