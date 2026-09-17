<?php
declare(strict_types=1);

$mostrarError = isset($_GET['error']);
$cssPath = dirname(__DIR__, 2) . '/public/assets/css/landing.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · API SUNAT Perú</title>
    <meta name="description" content="Accede al panel administrativo de facturación electrónica SUNAT.">
    <script>
        (() => {
            const saved = localStorage.getItem('landing-theme');
            const dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/landing.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="grain landing-grid min-h-screen bg-paper text-ink antialiased dark:bg-[#050505] dark:text-white">
    <div class="relative flex min-h-screen flex-col overflow-hidden">
        <header class="relative z-10 flex h-20 items-center justify-between border-b border-ink/10 px-5 sm:px-8 lg:px-12 dark:border-white/10">
            <a href="/" class="group flex items-center gap-3" aria-label="Volver al inicio">
                <span class="grid h-10 w-10 place-items-center bg-ink text-xs font-black text-signal transition-transform group-hover:-rotate-3 dark:bg-signal dark:text-black">CM</span>
                <span><strong class="block font-display text-sm font-extrabold uppercase tracking-[0.17em]">SUNAT API</strong><small class="block text-[10px] font-bold uppercase tracking-[0.18em] text-ink/45 dark:text-white/45">by CodeMultiAll</small></span>
            </a>
            <button id="theme-toggle" type="button" class="grid h-10 w-10 place-items-center border border-ink/15 transition hover:border-ink dark:border-white/15 dark:hover:border-signal" aria-label="Cambiar tema">
                <svg class="h-4 w-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a9 9 0 1 0 9 9c0-.5 0-1-.1-1.5A7 7 0 0 1 13.5 3H12Z"/></svg>
                <svg class="hidden h-4 w-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
            </button>
        </header>

        <main class="relative z-[1] grid flex-1 lg:grid-cols-[1.05fr_.95fr]">
            <section class="relative hidden overflow-hidden border-r border-ink/10 p-12 lg:flex lg:flex-col lg:justify-between dark:border-white/10">
                <div class="absolute -left-24 bottom-20 h-80 w-80 rounded-full border-[55px] border-[#dce900]/30 dark:border-signal/10"></div>
                <div class="relative max-w-xl pt-10">
                    <p class="flex items-center gap-3 text-[11px] font-extrabold uppercase tracking-[0.24em] text-[#657500] dark:text-signal"><span class="h-px w-10 bg-current"></span>Panel de gestión</p>
                    <h1 class="mt-8 font-display text-6xl font-extrabold leading-[0.95] tracking-[-0.055em] xl:text-7xl">Facturación<br>sin fricción.</h1>
                    <p class="mt-7 max-w-lg text-base leading-8 text-ink/60 dark:text-white/55">Administra empresas, usuarios, certificados, series y tokens desde un entorno centralizado.</p>
                </div>
                <div class="relative grid max-w-xl grid-cols-3 border border-ink/15 bg-white/45 dark:border-white/10 dark:bg-white/[0.035]">
                    <div class="border-r border-ink/10 p-5 dark:border-white/10"><strong class="block font-display text-xl">01</strong><span class="mt-2 block text-[10px] font-bold uppercase tracking-[0.14em] text-ink/45 dark:text-white/40">Empresas</span></div>
                    <div class="border-r border-ink/10 p-5 dark:border-white/10"><strong class="block font-display text-xl">02</strong><span class="mt-2 block text-[10px] font-bold uppercase tracking-[0.14em] text-ink/45 dark:text-white/40">Usuarios</span></div>
                    <div class="p-5"><strong class="block font-display text-xl">03</strong><span class="mt-2 block text-[10px] font-bold uppercase tracking-[0.14em] text-ink/45 dark:text-white/40">Comprobantes</span></div>
                </div>
            </section>

            <section class="flex items-center justify-center px-5 py-12 sm:px-10 lg:px-16">
                <div class="w-full max-w-md">
                    <div class="mb-9 lg:hidden">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-[#657500] dark:text-signal">Panel de gestión</p>
                        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-[-0.04em]">Bienvenido de nuevo.</h1>
                    </div>

                    <div class="border border-ink/15 bg-paper p-6 shadow-[12px_12px_0_rgb(7_17_31_/_0.08)] sm:p-9 dark:border-white/12 dark:bg-[#0a0a0a] dark:shadow-[12px_12px_0_rgb(234_255_0_/_0.08)]">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-[#657500] dark:text-signal">Acceso seguro</p>
                        <h2 class="mt-3 font-display text-3xl font-extrabold tracking-[-0.04em]">Iniciar sesión</h2>
                        <p class="mt-3 text-sm leading-6 text-ink/50 dark:text-white/45">Ingresa tus credenciales para acceder al panel administrativo.</p>

                        <form action="/login" method="POST" class="mt-8 space-y-5">
                            <label class="block">
                                <span class="mb-2 block text-[11px] font-extrabold uppercase tracking-[0.13em] text-ink/55 dark:text-white/55">Correo electrónico</span>
                                <span class="relative block"><svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/35 dark:text-white/35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="m4 6 8 6 8-6"/></svg><input type="email" name="email" autocomplete="email" required autofocus placeholder="tu@empresa.com" class="h-13 w-full border border-ink/15 bg-white/55 pl-11 pr-4 text-sm font-semibold outline-none transition placeholder:text-ink/30 focus:border-ink focus:ring-2 focus:ring-ink/10 dark:border-white/15 dark:bg-black dark:placeholder:text-white/25 dark:focus:border-signal dark:focus:ring-signal/10"></span>
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-[11px] font-extrabold uppercase tracking-[0.13em] text-ink/55 dark:text-white/55">Contraseña</span>
                                <span class="relative block"><svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/35 dark:text-white/35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="password" type="password" name="password" autocomplete="current-password" required class="h-13 w-full border border-ink/15 bg-white/55 pl-11 pr-12 text-sm font-semibold outline-none transition focus:border-ink focus:ring-2 focus:ring-ink/10 dark:border-white/15 dark:bg-black dark:focus:border-signal dark:focus:ring-signal/10"><button id="toggle-password" type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-extrabold uppercase tracking-[0.1em] text-ink/45 hover:text-ink dark:text-white/40 dark:hover:text-signal" aria-label="Mostrar contraseña">Ver</button></span>
                            </label>
                            <button type="submit" class="flex h-13 w-full items-center justify-center gap-3 bg-ink text-xs font-extrabold uppercase tracking-[0.15em] text-white transition hover:-translate-y-0.5 hover:shadow-[6px_6px_0_#dce900] dark:bg-signal dark:text-black dark:hover:shadow-[6px_6px_0_#fff]">Ingresar al panel <span aria-hidden="true">→</span></button>
                        </form>

                        <div class="mt-7 flex items-center justify-between border-t border-ink/10 pt-5 text-xs dark:border-white/10"><a href="/" class="font-semibold text-ink/50 transition hover:text-ink dark:text-white/45 dark:hover:text-white">← Volver al inicio</a><span class="text-ink/35 dark:text-white/30">Sesión protegida</span></div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div id="login-error-modal" class="fixed inset-0 z-50 <?= $mostrarError ? 'flex' : 'hidden' ?> items-center justify-center bg-black/70 p-5 backdrop-blur-sm" role="alertdialog" aria-modal="true" aria-labelledby="login-error-title">
        <div class="w-full max-w-sm border border-red-400/40 bg-paper p-6 shadow-[10px_10px_0_rgb(220_75_64_/_0.28)] dark:bg-[#0b0b0b]">
            <div class="flex items-start justify-between gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center border border-red-500/40 bg-red-500/10 text-red-600 dark:text-red-400"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg></span>
                <button type="button" data-close-error class="grid h-9 w-9 place-items-center border border-ink/15 text-lg transition hover:border-ink dark:border-white/15 dark:hover:border-white" aria-label="Cerrar mensaje">×</button>
            </div>
            <p class="mt-6 text-[10px] font-extrabold uppercase tracking-[0.2em] text-red-600 dark:text-red-400">Acceso denegado</p>
            <h2 id="login-error-title" class="mt-2 font-display text-2xl font-extrabold tracking-[-0.035em]">No pudimos iniciar sesión</h2>
            <p class="mt-3 text-sm leading-6 text-ink/55 dark:text-white/50">El correo o la contraseña no son correctos. Revisa los datos e inténtalo nuevamente.</p>
            <button type="button" data-close-error class="mt-6 h-11 w-full bg-ink text-xs font-extrabold uppercase tracking-[0.13em] text-white dark:bg-signal dark:text-black">Intentar nuevamente</button>
        </div>
    </div>

    <script>
        const root=document.documentElement;
        document.getElementById('theme-toggle').addEventListener('click',()=>{const dark=!root.classList.contains('dark');root.classList.toggle('dark',dark);localStorage.setItem('landing-theme',dark?'dark':'light')});
        const password=document.getElementById('password'),togglePassword=document.getElementById('toggle-password');
        togglePassword.addEventListener('click',()=>{const visible=password.type==='text';password.type=visible?'password':'text';togglePassword.textContent=visible?'Ver':'Ocultar';togglePassword.setAttribute('aria-label',visible?'Mostrar contraseña':'Ocultar contraseña')});
        const errorModal=document.getElementById('login-error-modal');
        function closeError(){errorModal.classList.add('hidden');errorModal.classList.remove('flex');document.querySelector('input[name="email"]')?.focus();if(history.replaceState)history.replaceState({},'', '/login')}
        document.querySelectorAll('[data-close-error]').forEach(button=>button.addEventListener('click',closeError));
        errorModal.addEventListener('mousedown',event=>{if(event.target===errorModal)closeError()});
        document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!errorModal.classList.contains('hidden'))closeError()});
    </script>
</body>
</html>
