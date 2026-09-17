<?php

declare(strict_types=1);

$cssPath = dirname(__DIR__, 2) . '/public/assets/css/landing.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
$whatsappUrl = 'https://wa.me/51979829261?text=' . rawurlencode(
    'Hola, quiero información sobre la API de Facturación Electrónica SUNAT.'
);
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Solicita acceso a Factura API y conversa con nuestro equipo sobre tu integración SUNAT.">
    <meta name="theme-color" content="#070707">
    <title>Contactar — Factura API</title>
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
    <header class="fixed inset-x-0 top-0 z-40 border-b border-black/10 bg-paper/85 backdrop-blur-xl dark:border-white/10 dark:bg-[#070707]/85">
        <nav class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8" aria-label="Navegación principal">
            <a href="/" class="group flex items-center gap-3" aria-label="Factura API, inicio">
                <span class="grid size-10 rotate-3 place-items-center bg-ink text-sm font-black text-signal transition-transform group-hover:rotate-0 dark:bg-signal dark:text-black">F/</span>
                <span class="hidden font-display text-lg font-extrabold tracking-[-0.04em] sm:inline">FACTURA<span class="text-[#657500] dark:text-signal">API</span></span>
            </a>
            <div class="flex items-center gap-2">
                <a href="/doc" class="hidden border border-black/15 px-4 py-2.5 text-sm font-bold transition hover:border-black hover:bg-white/60 dark:border-white/15 dark:hover:border-white dark:hover:bg-white/5 sm:inline-flex">Documentación</a>
                <button type="button" data-theme-toggle class="grid size-10 place-items-center border border-black/15 bg-white/50 transition hover:-translate-y-0.5 hover:border-black dark:border-white/15 dark:bg-white/5 dark:hover:border-signal" aria-label="Cambiar tema" aria-pressed="false">
                    <svg class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
                    <svg class="size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.6 6.6 0 0 0 21 12.8Z"/></svg>
                </button>
                <a href="/" class="grid size-10 place-items-center bg-ink text-lg text-white transition hover:-translate-x-0.5 dark:bg-signal dark:text-black" aria-label="Volver al inicio">←</a>
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-5 pb-16 pt-32 lg:px-8 lg:pb-24 lg:pt-40">
        <div class="grid items-start gap-12 lg:grid-cols-[1.08fr_.92fr] lg:gap-20">
            <section class="order-1 border border-black/12 bg-[#fffdf7]/80 p-5 shadow-[12px_12px_0_rgba(7,17,31,.1)] backdrop-blur-sm dark:border-white/12 dark:bg-[#101010]/90 dark:shadow-[12px_12px_0_rgba(234,255,0,.1)] sm:p-8 lg:p-10" aria-labelledby="form-title">
                <div class="mb-9 flex items-start justify-between gap-5">
                    <div>
                        <p class="font-mono text-[10px] font-bold uppercase tracking-[.2em] text-[#657500] dark:text-signal">Solicitud de acceso</p>
                        <h1 id="form-title" class="mt-3 font-display text-3xl font-extrabold tracking-[-.045em] sm:text-4xl">Cuéntanos sobre tu empresa.</h1>
                    </div>
                    <span class="hidden size-11 rotate-3 items-center justify-center bg-signal font-display text-sm font-black text-black sm:flex">01</span>
                </div>

                <form id="requestForm" class="grid gap-5" novalidate>
                    <div>
                        <label for="nombre" class="mb-2 block text-xs font-bold uppercase tracking-[.12em] text-black/55 dark:text-white/55">Tu nombre</label>
                        <input id="nombre" name="nombre" type="text" autocomplete="name" maxlength="100" required class="w-full border border-black/15 bg-white/55 px-4 py-3.5 text-sm outline-none transition placeholder:text-black/30 focus:border-[#657500] focus:ring-2 focus:ring-[#dce900]/25 dark:border-white/15 dark:bg-white/5 dark:placeholder:text-white/25 dark:focus:border-signal" placeholder="Nombre y apellido">
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="email" class="mb-2 block text-xs font-bold uppercase tracking-[.12em] text-black/55 dark:text-white/55">Correo electrónico</label>
                            <input id="email" name="email" type="email" autocomplete="email" maxlength="150" required class="w-full border border-black/15 bg-white/55 px-4 py-3.5 text-sm outline-none transition placeholder:text-black/30 focus:border-[#657500] focus:ring-2 focus:ring-[#dce900]/25 dark:border-white/15 dark:bg-white/5 dark:placeholder:text-white/25 dark:focus:border-signal" placeholder="tu@empresa.com">
                        </div>
                        <div>
                            <label for="telefono" class="mb-2 block text-xs font-bold uppercase tracking-[.12em] text-black/55 dark:text-white/55">Teléfono / WhatsApp</label>
                            <input id="telefono" name="telefono" type="tel" autocomplete="tel" maxlength="20" required class="w-full border border-black/15 bg-white/55 px-4 py-3.5 text-sm outline-none transition placeholder:text-black/30 focus:border-[#657500] focus:ring-2 focus:ring-[#dce900]/25 dark:border-white/15 dark:bg-white/5 dark:placeholder:text-white/25 dark:focus:border-signal" placeholder="+51 999 999 999">
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-[1.35fr_.65fr]">
                        <div>
                            <label for="empresa" class="mb-2 block text-xs font-bold uppercase tracking-[.12em] text-black/55 dark:text-white/55">Razón social</label>
                            <input id="empresa" name="empresa" type="text" autocomplete="organization" maxlength="180" required class="w-full border border-black/15 bg-white/55 px-4 py-3.5 text-sm outline-none transition placeholder:text-black/30 focus:border-[#657500] focus:ring-2 focus:ring-[#dce900]/25 dark:border-white/15 dark:bg-white/5 dark:placeholder:text-white/25 dark:focus:border-signal" placeholder="Nombre de la empresa">
                        </div>
                        <div>
                            <label for="ruc" class="mb-2 block text-xs font-bold uppercase tracking-[.12em] text-black/55 dark:text-white/55">RUC</label>
                            <input id="ruc" name="ruc" type="text" inputmode="numeric" autocomplete="off" minlength="11" maxlength="11" pattern="[0-9]{11}" required class="w-full border border-black/15 bg-white/55 px-4 py-3.5 font-mono text-sm outline-none transition placeholder:text-black/30 focus:border-[#657500] focus:ring-2 focus:ring-[#dce900]/25 dark:border-white/15 dark:bg-white/5 dark:placeholder:text-white/25 dark:focus:border-signal" placeholder="20123456789" title="Ingresa los 11 dígitos del RUC">
                        </div>
                    </div>

                    <div id="requestAlert" class="request-alert hidden border px-4 py-3 text-sm leading-6" role="status" aria-live="polite"></div>

                    <div class="mt-1 flex flex-col gap-4 border-t border-black/10 pt-6 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                        <p class="max-w-xs text-xs leading-5 text-black/45 dark:text-white/40">Usaremos estos datos únicamente para responder tu solicitud comercial.</p>
                        <button id="requestSubmit" type="submit" class="group inline-flex min-w-52 items-center justify-center gap-3 bg-ink px-6 py-4 text-sm font-extrabold uppercase tracking-[.1em] text-white transition hover:-translate-y-1 disabled:cursor-wait disabled:opacity-60 dark:bg-signal dark:text-black">
                            <span data-submit-label>Enviar solicitud</span>
                            <span class="transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>
            </section>

            <section class="order-2 lg:sticky lg:top-32" aria-labelledby="contact-brand-title">
                <div class="inline-flex items-center gap-3 border border-black/15 bg-white/55 px-3 py-2 font-mono text-[10px] font-bold uppercase tracking-[.18em] dark:border-white/15 dark:bg-white/5"><span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-[#718000] opacity-70 dark:bg-signal"></span><span class="relative inline-flex size-2 rounded-full bg-[#718000] dark:bg-signal"></span></span> Hablemos de tu integración</div>
                <h2 id="contact-brand-title" class="mt-7 font-display text-[2.8rem] font-extrabold uppercase leading-[.88] tracking-[-.065em] min-[420px]:text-[3.25rem] sm:text-6xl lg:text-7xl">De tu sistema<br><span class="text-[#657500] dark:text-signal">a SUNAT.</span></h2>
                <p class="mt-7 max-w-xl text-base leading-7 text-black/60 dark:text-white/55">Revisamos tu operación, el volumen de comprobantes y la cantidad de empresas para proponerte una integración clara y un plan adecuado.</p>

                <div class="mt-10 grid gap-px border border-black/10 bg-black/10 dark:border-white/10 dark:bg-white/10 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                    <div class="bg-paper p-5 dark:bg-[#070707]"><span class="font-mono text-[10px] font-bold text-[#657500] dark:text-signal">01</span><strong class="mt-5 block font-display text-lg">Revisión</strong><p class="mt-2 text-xs leading-5 text-black/50 dark:text-white/45">Entendemos tu caso.</p></div>
                    <div class="bg-paper p-5 dark:bg-[#070707]"><span class="font-mono text-[10px] font-bold text-[#657500] dark:text-signal">02</span><strong class="mt-5 block font-display text-lg">Propuesta</strong><p class="mt-2 text-xs leading-5 text-black/50 dark:text-white/45">Definimos alcance y plan.</p></div>
                    <div class="bg-paper p-5 dark:bg-[#070707]"><span class="font-mono text-[10px] font-bold text-[#657500] dark:text-signal">03</span><strong class="mt-5 block font-display text-lg">Acceso</strong><p class="mt-2 text-xs leading-5 text-black/50 dark:text-white/45">Preparamos tu empresa.</p></div>
                </div>

                <div class="mt-6 flex flex-col gap-4 border-l-4 border-signal bg-ink p-5 text-white dark:bg-white/[.045] sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-mono text-[10px] uppercase tracking-[.18em] text-white/45">¿Prefieres escribir directamente?</p><p class="mt-1 font-bold">WhatsApp +51 979 829 261</p></div>
                    <a href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-extrabold text-signal underline decoration-2 underline-offset-4">Abrir chat <span>↗</span></a>
                </div>

                <p class="mt-8 text-xs text-black/40 dark:text-white/35">Producto creado por <a href="https://codemultiall.net" target="_blank" rel="noopener noreferrer" class="font-bold underline decoration-signal decoration-2 underline-offset-4">Codemultiall</a>.</p>
            </section>
        </div>
    </main>

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

        const form = document.getElementById('requestForm');
        const alertBox = document.getElementById('requestAlert');
        const submitButton = document.getElementById('requestSubmit');
        const submitLabel = submitButton.querySelector('[data-submit-label]');

        function showStatus(state, message) {
            alertBox.dataset.state = state;
            alertBox.textContent = message;
            alertBox.classList.remove('hidden');
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            submitButton.disabled = true;
            submitLabel.textContent = 'Enviando...';
            showStatus('loading', 'Enviando tu solicitud...');

            try {
                const response = await fetch('/request-access', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(Object.fromEntries(new FormData(form))),
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'No pudimos enviar la solicitud.');
                }

                showStatus('success', result.message);
                form.reset();
            } catch (error) {
                showStatus('error', error.message || 'Error de conexión. Inténtalo nuevamente.');
            } finally {
                submitButton.disabled = false;
                submitLabel.textContent = 'Enviar solicitud';
            }
        });
    </script>
</body>
</html>
