<?php
declare(strict_types=1);

$nombre = (string) ($usuario['nombre'] ?? 'Usuario');
$usuarioId = (int) ($usuario['sub'] ?? 0);
$db = \App\Facturacion\Config\Database::getConnection();
$empresaRepo = new \App\Facturacion\Repositories\EmpresaFacturacionRepository($db);
$empresas = $empresaRepo->findByUsuarioId($usuarioId);
$empresaId = (int) ($_GET['empresa_id'] ?? ($empresas[0]->id ?? 0));
$empresaActual = null;
foreach ($empresas as $empresa) {
    if ($empresa->id === $empresaId) { $empresaActual = $empresa; break; }
}
if (!$empresaActual && $empresas) { $empresaActual = $empresas[0]; $empresaId = $empresaActual->id; }
$productos = [];
$schemaReady = true;
if ($empresaActual) {
    try { $productos = (new \App\Facturacion\Repositories\ProductoRepository($db))->findByEmpresa($empresaId); }
    catch (\PDOException $exception) { $schemaReady = false; error_log('Productos: ' . $exception->getMessage()); }
}
$siguienteCodigo = 'PROD-001';
if ($productos) {
    $ultimoCodigo = trim((string) $productos[0]->codigo);
    if (preg_match('/^(.*?)(\d+)$/', $ultimoCodigo, $coincidencia) === 1) {
        $prefijo = $coincidencia[1];
        $numero = $coincidencia[2];
        $siguienteCodigo = $prefijo . str_pad(
            (string) ((int) $numero + 1),
            strlen($numero),
            '0',
            STR_PAD_LEFT
        );
    }
}
$e = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$cssPath = dirname(__DIR__, 2) . '/public/assets/css/landing.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos · Panel SUNAT</title>
    <script>(()=>{const s=localStorage.getItem('landing-theme'),d=s?s==='dark':matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.classList.toggle('dark',d)})()</script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/3.0.4/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="/public/assets/css/landing.css?v=<?= $cssVersion ?>">
</head>
<body class="min-h-screen bg-[#f4f6f8] text-[#182230] antialiased dark:bg-[#0b0d10] dark:text-[#edf0f3]" style="font-family:'IBM Plex Sans',sans-serif">
    <header class="border-b border-slate-200 bg-white dark:border-white/10 dark:bg-[#111419]">
        <div class="mx-auto flex h-16 max-w-[1440px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-8"><a href="/dashboard" class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-md bg-[#182230] text-xs font-bold text-signal dark:bg-signal dark:text-black">CM</span><span class="hidden sm:block"><strong class="block text-sm font-bold">Panel SUNAT</strong><small class="text-xs text-slate-500 dark:text-slate-400">Facturador interno</small></span></a><nav class="hidden items-center gap-1 md:flex"><a href="/dashboard" class="admin-nav-link">Empresas</a><?php if (($usuario['rol'] ?? '') === 'admin'): ?><a href="/usuarios" class="admin-nav-link">Usuarios</a><?php endif; ?><a href="/productos" class="admin-nav-link admin-nav-active">Productos</a><a href="/vender" class="admin-nav-link">Vender</a><a href="/ventas" class="admin-nav-link">Ventas</a></nav></div>
            <div class="flex items-center gap-2"><span class="hidden text-sm text-slate-500 sm:inline dark:text-slate-400"><?= $e($nombre) ?></span><button id="theme-toggle" class="admin-icon-button" type="button" aria-label="Cambiar tema"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg></button><a href="/logout" class="admin-icon-button" aria-label="Cerrar sesión"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17l5-5-5-5M15 12H3M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg></a></div>
        </div>
        <nav class="flex overflow-x-auto border-t border-slate-100 px-2 md:hidden dark:border-white/5"><a href="/dashboard" class="admin-mobile-link">Empresas</a><a href="/productos" class="admin-mobile-link border-b-2 border-[#9aac00] text-[#657500] dark:border-signal dark:text-signal">Productos</a><a href="/vender" class="admin-mobile-link">Vender</a><a href="/ventas" class="admin-mobile-link">Ventas</a></nav>
    </header>

    <main class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Catálogo de venta</p><h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Productos y servicios</h1><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Datos listos para construir el detalle del comprobante. Sin control de inventario.</p></div>
            <?php if ($empresaActual && $schemaReady): ?><button type="button" data-open-modal="modalProducto" class="admin-primary-button"><span class="text-lg leading-none">+</span> Nuevo producto</button><?php endif; ?>
        </div>

        <?php if (!$empresas): ?>
            <section class="mt-7 rounded-lg border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-white/10 dark:bg-[#111419]"><h2 class="text-lg font-bold">No tienes una empresa vinculada</h2><p class="mt-2 text-sm text-slate-500">Necesitas una empresa propia para administrar productos.</p></section>
        <?php else: ?>
            <section class="mt-7 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#111419]">
                <form method="GET" action="/productos" class="flex flex-col gap-3 sm:flex-row sm:items-end"><label class="min-w-0 flex-1"><span class="field-label">Empresa de trabajo</span><select name="empresa_id" class="field-input" onchange="this.form.submit()"><?php foreach ($empresas as $empresa): ?><option value="<?= $empresa->id ?>" <?= $empresa->id === $empresaId ? 'selected' : '' ?>><?= $e($empresa->nombreComercial ?: $empresa->razonSocial) ?> · <?= $e($empresa->ruc) ?></option><?php endforeach; ?></select></label><div class="rounded-md bg-slate-50 px-4 py-3 text-xs text-slate-500 dark:bg-white/5 dark:text-slate-400">Solo ves productos de empresas vinculadas a tu usuario.</div></form>
            </section>

            <?php if (!$schemaReady): ?><div class="form-alert mt-5" data-state="error">Falta ejecutar <code>sql/20260917_create_productos.sql</code> en la base de datos.</div><?php else: ?>
            <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111419]">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10"><h2 class="text-sm font-semibold">Catálogo de <?= $e($empresaActual?->nombreComercial ?: $empresaActual?->razonSocial) ?></h2><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Búsqueda por código o descripción · 10 registros por página.</p></div>
                <div class="overflow-x-auto p-4 sm:p-5"><table id="productosTable" class="display w-full" style="width:100%"><thead><tr><th>Código</th><th>Descripción</th><th>Unidad</th><th>Precio</th><th>IGV</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach ($productos as $producto): ?><tr><td><code class="font-semibold"><?= $e($producto->codigo) ?></code></td><td class="font-semibold"><?= $e($producto->descripcion) ?></td><td><?= $e($producto->unidad) ?></td><td data-order="<?= $producto->precioUnitario ?>">S/ <?= number_format($producto->precioUnitario, 2) ?></td><td><?= $e($producto->afectacionIgv) ?></td><td><span class="role-badge <?= $producto->activo ? 'role-client' : 'role-admin' ?>"><?= $producto->activo ? 'Activo' : 'Inactivo' ?></span></td><td><div class="flex gap-2"><button type="button" class="admin-secondary-button !min-h-0 !px-3 !py-2" data-edit='<?= $e(json_encode($producto->toArray(), JSON_UNESCAPED_UNICODE)) ?>'>Editar</button><button type="button" class="text-xs font-semibold text-red-600 hover:underline dark:text-red-400" data-delete="<?= $producto->id ?>">Eliminar</button></div></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <div id="modalProducto" class="app-modal" role="dialog" aria-modal="true"><div class="modal-panel max-w-2xl"><div class="modal-heading"><div><p class="modal-kicker">Catálogo</p><h2 id="productoTitle" class="modal-title">Nuevo producto</h2><p class="mt-2 text-sm text-slate-500">El precio gravado debe incluir IGV.</p></div><button type="button" data-close-modal class="modal-close">×</button></div><form id="productoForm" class="mt-7 grid gap-4 sm:grid-cols-2"><input type="hidden" name="id"><label><span class="field-label">Código</span><input class="field-input uppercase" name="codigo" maxlength="50" required value="<?= $e($siguienteCodigo) ?>" placeholder="PROD-001"><small class="mt-1 block text-xs text-slate-500">Se propone el correlativo siguiente, pero puedes cambiarlo.</small></label><label><span class="field-label">Descripción</span><input class="field-input" name="descripcion" maxlength="255" required placeholder="Producto o servicio"></label><label><span class="field-label">Unidad SUNAT</span><select class="field-input" name="unidad"><option value="NIU">NIU · Unidad</option><option value="ZZ">ZZ · Servicio</option><option value="KG">KG · Kilogramo</option><option value="BX">BX · Caja</option><option value="M">M · Metro</option><option value="L">L · Litro</option></select></label><label><span class="field-label">Precio unitario</span><input class="field-input" type="number" name="precio_unitario" min="0" step="0.01" required value="0.00"></label><label><span class="field-label">Afectación IGV</span><select class="field-input" name="afectacion_igv"><option value="10">10 · Gravado</option><option value="20">20 · Exonerado</option><option value="30">30 · Inafecto</option><option value="21">21 · Gratuito gravado</option></select></label><label><span class="field-label">Estado</span><select class="field-input" name="activo"><option value="1">Activo</option><option value="0">Inactivo</option></select></label></form><div id="productoAlert" class="form-alert mt-4 hidden"></div><div class="modal-actions"><button type="button" data-close-modal class="admin-secondary-button">Cancelar</button><button type="button" id="guardarProducto" class="admin-primary-button">Guardar producto</button></div></div></div>

    <script src="https://cdn.datatables.net/3.0.4/js/dataTables.js"></script>
    <script>
    const empresaId=<?= $empresaId ?>,siguienteCodigo=<?= json_encode($siguienteCodigo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,modal=document.getElementById('modalProducto'),form=document.getElementById('productoForm'),alertBox=document.getElementById('productoAlert');
    document.getElementById('theme-toggle').addEventListener('click',()=>{const d=!document.documentElement.classList.contains('dark');document.documentElement.classList.toggle('dark',d);localStorage.setItem('landing-theme',d?'dark':'light')});
    function openModal(){modal?.classList.add('flex');document.body.style.overflow='hidden'}function closeModal(){modal?.classList.remove('flex');document.body.style.overflow=''}
    document.querySelectorAll('[data-open-modal]').forEach(b=>b.addEventListener('click',()=>{form.reset();form.elements.id.value='';form.elements.codigo.value=siguienteCodigo;document.getElementById('productoTitle').textContent='Nuevo producto';alertBox.classList.add('hidden');openModal()}));document.querySelectorAll('[data-close-modal]').forEach(b=>b.addEventListener('click',closeModal));
    function showAlert(state,msg){alertBox.dataset.state=state;alertBox.textContent=msg;alertBox.classList.remove('hidden')}
    document.querySelectorAll('[data-edit]').forEach(b=>b.addEventListener('click',()=>{const p=JSON.parse(b.dataset.edit),fields=form.elements;fields.id.value=p.id;fields.codigo.value=p.codigo;fields.descripcion.value=p.descripcion;fields.unidad.value=p.unidad;fields.precio_unitario.value=Number(p.precio_unitario).toFixed(2);fields.afectacion_igv.value=p.afectacion_igv;fields.activo.value=p.activo?'1':'0';document.getElementById('productoTitle').textContent='Editar producto';alertBox.classList.add('hidden');openModal()}));
    document.getElementById('guardarProducto')?.addEventListener('click',async()=>{if(!form.checkValidity()){form.reportValidity();return}const data=Object.fromEntries(new FormData(form));data.empresa_id=empresaId;data.activo=data.activo==='1';const id=data.id;delete data.id;showAlert('loading','Guardando producto…');try{const r=await fetch(id?`/api/facturacion/productos/${id}`:'/api/facturacion/productos',{method:id?'PUT':'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(data)}),j=await r.json();if(!r.ok||!j.success)throw new Error(j.error?.message||'No se pudo guardar.');location.reload()}catch(e){showAlert('error',e.message)}});
    document.querySelectorAll('[data-delete]').forEach(b=>b.addEventListener('click',async()=>{if(!confirm('¿Eliminar este producto del catálogo?'))return;try{const r=await fetch(`/api/facturacion/productos/${b.dataset.delete}`,{method:'DELETE',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({empresa_id:empresaId})}),j=await r.json();if(!r.ok||!j.success)throw new Error(j.error?.message||'No se pudo eliminar.');location.reload()}catch(e){alert(e.message)}}));
    if(window.DataTable&&document.getElementById('productosTable'))new DataTable('#productosTable',{pageLength:10,lengthChange:false,order:[[0,'asc']],columnDefs:[{orderable:false,targets:6}],language:{search:'Buscar:',searchPlaceholder:'Código o descripción…',info:'Mostrando _START_ a _END_ de _TOTAL_ productos',infoEmpty:'Sin productos',zeroRecords:'No se encontraron productos',emptyTable:'No hay productos registrados',paginate:{previous:'Anterior',next:'Siguiente'}}});
    </script>
</body></html>
