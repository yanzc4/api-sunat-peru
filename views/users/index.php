<?php
declare(strict_types=1);

$nombre = (string) ($usuario['nombre'] ?? 'Administrador');
$db = \App\Facturacion\Config\Database::getConnection();
$usuarioRepo = new \App\Facturacion\Repositories\UsuarioRepository($db);
$usuarios = $usuarioRepo->findAllWithEmpresas();
$e = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$cssPath = dirname(__DIR__, 2) . '/public/assets/css/landing.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios · Panel SUNAT</title>
    <script>
        (() => {
            const saved = localStorage.getItem('landing-theme');
            const dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/3.0.4/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="/public/assets/css/landing.css?v=<?= $cssVersion ?>">
</head>
<body class="min-h-screen bg-[#f4f6f8] text-[#182230] antialiased dark:bg-[#0b0d10] dark:text-[#edf0f3]" style="font-family:'IBM Plex Sans',sans-serif">
    <header class="border-b border-slate-200 bg-white dark:border-white/10 dark:bg-[#111419]">
        <div class="mx-auto flex h-16 max-w-[1440px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-8">
                <a href="/dashboard" class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-md bg-[#182230] text-xs font-bold text-signal dark:bg-signal dark:text-black">CM</span><span class="hidden sm:block"><strong class="block text-sm font-bold">Panel SUNAT</strong><small class="text-xs text-slate-500 dark:text-slate-400">Administración</small></span></a>
                <nav class="hidden items-center gap-1 md:flex" aria-label="Navegación principal"><a href="/dashboard" class="admin-nav-link">Empresas</a><a href="/usuarios" class="admin-nav-link admin-nav-active">Usuarios</a><a href="/productos" class="admin-nav-link">Productos</a><a href="/vender" class="admin-nav-link">Vender</a><a href="/ventas" class="admin-nav-link">Ventas</a></nav>
            </div>
            <div class="flex items-center gap-2"><span class="hidden text-sm text-slate-500 sm:inline dark:text-slate-400"><?= $e($nombre) ?></span><button id="theme-toggle" class="admin-icon-button" type="button" aria-label="Cambiar tema"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg></button><a href="/logout" class="admin-icon-button" aria-label="Cerrar sesión"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17l5-5-5-5M15 12H3M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg></a></div>
        </div>
        <nav class="flex overflow-x-auto border-t border-slate-100 px-2 md:hidden dark:border-white/5"><a href="/dashboard" class="admin-mobile-link">Empresas</a><a href="/usuarios" class="admin-mobile-link border-b-2 border-[#9aac00] text-[#657500] dark:border-signal dark:text-signal">Usuarios</a><a href="/productos" class="admin-mobile-link">Productos</a><a href="/vender" class="admin-mobile-link">Vender</a><a href="/ventas" class="admin-mobile-link">Ventas</a></nav>
    </header>

    <main class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Administración</p><h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Usuarios</h1><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Crea primero el acceso del cliente y luego asígnalo al registrar su empresa.</p></div>
            <button type="button" data-open-modal="modalCrearUsuario" class="admin-primary-button"><span class="text-lg leading-none">+</span> Nuevo usuario</button>
        </div>

        <div class="mt-7 grid gap-4 sm:grid-cols-3">
            <div class="admin-stat"><span>Total de usuarios</span><strong><?= count($usuarios) ?></strong></div>
            <div class="admin-stat"><span>Clientes</span><strong><?= count(array_filter($usuarios, fn(array $u): bool => $u['rol'] === 'cliente')) ?></strong></div>
            <div class="admin-stat"><span>Con empresa</span><strong><?= count(array_filter($usuarios, fn(array $u): bool => !empty($u['empresas']))) ?></strong></div>
        </div>

        <section class="mt-7 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111419]">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10"><h2 class="text-sm font-semibold">Directorio de usuarios</h2><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">La tabla muestra 10 registros por página.</p></div>
            <div class="overflow-x-auto p-4 sm:p-5">
                <table id="usuariosTable" class="display w-full" style="width:100%">
                    <thead><tr><th>ID</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Empresa vinculada</th><th>Fecha de alta</th></tr></thead>
                    <tbody>
                        <?php foreach ($usuarios as $item): ?>
                            <tr>
                                <td><?= (int) $item['id'] ?></td>
                                <td><span class="font-semibold"><?= $e($item['nombre']) ?></span></td>
                                <td><?= $e($item['email']) ?></td>
                                <td><span class="role-badge <?= $item['rol'] === 'admin' ? 'role-admin' : 'role-client' ?>"><?= $e($item['rol']) ?></span></td>
                                <td><?= $item['empresas'] ? '<span class="company-cell">' . $e($item['empresas']) . '</span>' : '' ?></td>
                                <td data-order="<?= $e($item['created_at']) ?>"><?= $e(date('d/m/Y', strtotime((string) $item['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="tableFallbackControls" class="hidden items-center justify-between gap-4 border-t border-slate-200 pt-4 text-sm dark:border-white/10"><span id="fallbackInfo"></span><div class="flex gap-2"><button id="fallbackPrev" class="admin-secondary-button">Anterior</button><button id="fallbackNext" class="admin-secondary-button">Siguiente</button></div></div>
            </div>
        </section>
    </main>

    <div id="modalCrearUsuario" class="app-modal" role="dialog" aria-modal="true" aria-labelledby="titulo-crear-usuario">
        <div class="modal-panel max-w-lg">
            <div class="modal-heading"><div><p class="modal-kicker">Nuevo acceso</p><h2 id="titulo-crear-usuario" class="modal-title">Crear usuario</h2><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Después podrás seleccionarlo al crear una empresa.</p></div><button type="button" data-close-modal class="modal-close">×</button></div>
            <form id="formCrearUsuario" class="mt-6 space-y-4">
                <label><span class="field-label">Nombre completo</span><input class="field-input" name="nombre" autocomplete="name" required></label>
                <label><span class="field-label">Correo electrónico</span><input class="field-input" type="email" name="email" autocomplete="email" required></label>
                <label><span class="field-label">Contraseña temporal</span><input class="field-input" type="password" name="password" minlength="8" autocomplete="new-password" required><small class="mt-1 block text-xs text-slate-500">Mínimo 8 caracteres.</small></label>
                <label><span class="field-label">Rol</span><select class="field-input" name="rol"><option value="cliente">Cliente</option><option value="admin">Administrador</option></select></label>
            </form>
            <div id="resUsuario" class="form-alert mt-4 hidden"></div>
            <div class="modal-actions"><button type="button" data-close-modal class="admin-secondary-button">Cancelar</button><button type="button" id="guardarUsuario" class="admin-primary-button">Crear usuario</button></div>
        </div>
    </div>

    <script src="https://cdn.datatables.net/3.0.4/js/dataTables.js"></script>
    <script>
        const themeToggle=document.getElementById('theme-toggle');themeToggle.addEventListener('click',()=>{const dark=!document.documentElement.classList.contains('dark');document.documentElement.classList.toggle('dark',dark);localStorage.setItem('landing-theme',dark?'dark':'light')});
        function openModal(id){const modal=document.getElementById(id);if(!modal)return;modal.classList.add('flex');document.body.style.overflow='hidden';setTimeout(()=>modal.querySelector('input,button,select')?.focus(),20)}
        function closeModal(modal){modal.classList.remove('flex');document.body.style.overflow=''}
        document.querySelectorAll('[data-open-modal]').forEach(button=>button.addEventListener('click',()=>openModal(button.dataset.openModal)));document.querySelectorAll('[data-close-modal]').forEach(button=>button.addEventListener('click',()=>closeModal(button.closest('.app-modal'))));document.querySelectorAll('.app-modal').forEach(modal=>modal.addEventListener('mousedown',event=>{if(event.target===modal)closeModal(modal)}));document.addEventListener('keydown',event=>{if(event.key==='Escape')document.querySelectorAll('.app-modal.flex').forEach(closeModal)});
        function setAlert(state,message){const element=document.getElementById('resUsuario');element.dataset.state=state;element.textContent=message;element.classList.remove('hidden')}
        async function readJson(response){try{return await response.json()}catch(_){return{success:false,error:{message:'Respuesta no válida del servidor.'}}}}
        document.getElementById('guardarUsuario').addEventListener('click',async()=>{const form=document.getElementById('formCrearUsuario');if(!form.checkValidity()){form.reportValidity();return}setAlert('loading','Creando usuario…');try{const response=await fetch('/api/facturacion/usuarios',{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(Object.fromEntries(new FormData(form)))}),json=await readJson(response);if(!response.ok||!json.success)throw new Error(json.error?.message||'No se pudo crear el usuario.');location.reload()}catch(error){setAlert('error',error.message)}});

        if(window.DataTable){new DataTable('#usuariosTable',{pageLength:10,lengthChange:false,order:[[0,'desc']],layout:{topStart:'search',topEnd:null,bottomStart:'info',bottomEnd:'paging'},language:{search:'Buscar:',searchPlaceholder:'Nombre, correo o empresa…',info:'Mostrando _START_ a _END_ de _TOTAL_ usuarios',infoEmpty:'Sin usuarios',zeroRecords:'No se encontraron usuarios',emptyTable:'No hay usuarios registrados',paginate:{previous:'Anterior',next:'Siguiente'}}})}else{initFallbackTable()}
        function initFallbackTable(){const rows=[...document.querySelectorAll('#usuariosTable tbody tr')],controls=document.getElementById('tableFallbackControls'),info=document.getElementById('fallbackInfo'),prev=document.getElementById('fallbackPrev'),next=document.getElementById('fallbackNext');let page=0;const pages=Math.max(1,Math.ceil(rows.length/10));controls.classList.remove('hidden');controls.classList.add('flex');const draw=()=>{rows.forEach((row,index)=>row.hidden=index<page*10||index>=(page+1)*10);info.textContent=`Página ${page+1} de ${pages}`;prev.disabled=page===0;next.disabled=page>=pages-1};prev.addEventListener('click',()=>{if(page>0){page--;draw()}});next.addEventListener('click',()=>{if(page<pages-1){page++;draw()}});draw()}
    </script>
</body>
</html>
