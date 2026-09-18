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
    if ($empresa->id === $empresaId) {
        $empresaActual = $empresa;
        break;
    }
}
if (!$empresaActual && $empresas) {
    $empresaActual = $empresas[0];
    $empresaId = $empresaActual->id;
}
$ventas = [];
if ($empresaActual) {
    $ventas = array_values(array_filter(
        (new \App\Facturacion\Repositories\ComprobanteRepository($db))->findAll(['empresa_id' => $empresaId]),
        static fn($comprobante): bool => in_array($comprobante->tipoComprobante, ['01', '03'], true)
    ));
}
$totalVendido = array_reduce($ventas, static fn(float $total, $venta): float => $total + $venta->total, 0.0);
$totalIgv = array_reduce($ventas, static fn(float $total, $venta): float => $total + $venta->igv, 0.0);
$e = static fn(?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$cssPath = dirname(__DIR__, 2) . '/public/assets/css/landing.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas · Panel SUNAT</title>
    <script>
        (() => {
            const s = localStorage.getItem('landing-theme'),
                d = s ? s === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', d)
        })()
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
            <div class="flex items-center gap-8"><a href="/dashboard" class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-md bg-[#182230] text-xs font-bold text-signal dark:bg-signal dark:text-black">CM</span><span class="hidden sm:block"><strong class="block text-sm font-bold">Panel SUNAT</strong><small class="text-xs text-slate-500 dark:text-slate-400">Facturador interno</small></span></a>
                <nav class="hidden items-center gap-1 md:flex"><a href="/dashboard" class="admin-nav-link">Empresas</a><?php if (($usuario['rol'] ?? '') === 'admin'): ?><a href="/usuarios" class="admin-nav-link">Usuarios</a><?php endif; ?><a href="/productos" class="admin-nav-link">Productos</a><a href="/vender" class="admin-nav-link">Vender</a><a href="/ventas" class="admin-nav-link admin-nav-active">Ventas</a></nav>
            </div>
            <div class="flex items-center gap-2"><span class="hidden text-sm text-slate-500 sm:inline dark:text-slate-400"><?= $e($nombre) ?></span><button id="theme-toggle" class="admin-icon-button" type="button" aria-label="Cambiar tema"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg></button><a href="/logout" class="admin-icon-button" aria-label="Cerrar sesión"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17l5-5-5-5M15 12H3M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg></a></div>
        </div>
        <nav class="flex overflow-x-auto border-t border-slate-100 px-2 md:hidden dark:border-white/5"><a href="/dashboard" class="admin-mobile-link">Empresas</a><a href="/productos" class="admin-mobile-link">Productos</a><a href="/vender" class="admin-mobile-link">Vender</a><a href="/ventas" class="admin-mobile-link border-b-2 border-[#9aac00] text-[#657500] dark:border-signal dark:text-signal">Ventas</a></nav>
    </header>

    <main class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Historial comercial</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Ventas emitidas</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Consulta los comprobantes de venta y recupera su PDF sin exponer rutas públicas.</p>
            </div><a href="/vender" class="admin-primary-button">+ Nueva venta</a>
        </div>
        <?php if (!$empresas): ?><section class="mt-7 rounded-lg border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-white/10 dark:bg-[#111419]">
                <h2 class="text-lg font-bold">No tienes una empresa vinculada</h2>
            </section><?php else: ?>
            <section class="mt-7 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#111419]">
                <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end"><label class="min-w-0 flex-1"><span class="field-label">Empresa</span><select name="empresa_id" class="field-input" onchange="this.form.submit()"><?php foreach ($empresas as $empresa): ?><option value="<?= $empresa->id ?>" <?= $empresa->id === $empresaId ? 'selected' : '' ?>><?= $e($empresa->nombreComercial ?: $empresa->razonSocial) ?> · <?= $e($empresa->ruc) ?></option><?php endforeach; ?></select></label>
                    <div class="rounded-md bg-slate-50 px-4 py-3 text-xs text-slate-500 dark:bg-white/5">El historial también permanece visible si la empresa está suspendida.</div>
                </form>
            </section>
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="admin-stat"><span>Comprobantes</span><strong><?= count($ventas) ?></strong></div>
                <div class="admin-stat"><span>Monto emitido</span><strong><?= $e($empresaActual ? 'S/ ' . number_format($totalVendido, 2) : '—') ?></strong></div>
                <div class="admin-stat"><span>IGV acumulado</span><strong><?= $e($empresaActual ? 'S/ ' . number_format($totalIgv, 2) : '—') ?></strong></div>
            </div>
            <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111419]">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                    <h2 class="text-sm font-semibold">Comprobantes de venta</h2>
                    <p class="mt-1 text-xs text-slate-500">10 registros por página.</p>
                </div>
                <div class="overflow-x-auto p-4 sm:p-5">
                    <table id="ventasTable" class="display w-full" style="width:100%">
                        <thead>
                            <tr>
                                <th>Comprobante</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Monto pagado</th>
                                <th>IGV</th>
                                <th>Estado</th>
                                <th>Ver</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($ventas as $venta): ?><tr>
                                    <td><strong><?= $e($venta->getNumeroFormato()) ?></strong><small class="block text-slate-400"><?= $venta->tipoComprobante === '01' ? 'Factura' : 'Boleta' ?></small></td>
                                    <td><strong class="block"><?= $e($venta->clienteNombre) ?></strong><small class="text-slate-400"><?= $e($venta->clienteNumeroDocumento) ?></small></td>
                                    <td data-order="<?= $e($venta->fechaEmision . ' ' . ($venta->horaEmision ?? '')) ?>"><?= $e(date('d/m/Y', strtotime($venta->fechaEmision))) ?></td>
                                    <td data-order="<?= $venta->total ?>"><strong><?= $venta->moneda === 'USD' ? '$' : 'S/' ?> <?= number_format($venta->total, 2) ?></strong></td>
                                    <td data-order="<?= $venta->igv ?>"><?= $venta->moneda === 'USD' ? '$' : 'S/' ?> <?= number_format($venta->igv, 2) ?></td>
                                    <td><span class="role-badge <?= $venta->estado === 'aceptado' ? 'role-client' : 'role-admin' ?>"><?= $e($venta->estado) ?></span></td>
                                    <td><button type="button" class="admin-icon-button !h-9 !w-9" data-view-sale="<?= $venta->id ?>" aria-label="Ver <?= $e($venta->getNumeroFormato()) ?>"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                                <circle cx="12" cy="12" r="2.5" />
                                            </svg></button></td>
                                </tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <div id="saleDetailModal" class="app-modal" role="dialog" aria-modal="true">
        <div class="modal-panel max-w-4xl">
            <div class="modal-heading">
                <div>
                    <p class="modal-kicker">Detalle de venta</p>
                    <h2 id="detailTitle" class="modal-title">Comprobante</h2>
                    <p id="detailSubtitle" class="mt-2 text-sm text-slate-500"></p>
                </div><button type="button" data-close-detail class="modal-close">×</button>
            </div>
            <div id="detailLoading" class="py-16 text-center"><span class="sale-spinner mx-auto"></span>
                <p class="mt-4 text-sm text-slate-500">Cargando comprobante…</p>
            </div>
            <div id="detailContent" class="hidden">
                <div id="detailSummary" class="mt-6 grid gap-3 sm:grid-cols-3"></div>
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[620px] border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-[11px] uppercase tracking-wider text-slate-500 dark:border-white/10">
                                <th class="px-3 py-3">Producto</th>
                                <th class="px-3 py-3 text-right">Cantidad</th>
                                <th class="px-3 py-3 text-right">Precio</th>
                                <th class="px-3 py-3 text-right">IGV</th>
                                <th class="px-3 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="detailItems"></tbody>
                    </table>
                </div>
                <div class="mt-5 flex justify-end">
                    <div class="w-full max-w-xs space-y-2 border-t border-slate-200 pt-4 text-sm dark:border-white/10">
                        <div class="flex justify-between"><span>Valor de venta</span><strong id="detailSubtotal"></strong></div>
                        <div class="flex justify-between"><span>IGV</span><strong id="detailIgv"></strong></div>
                        <div class="flex justify-between text-lg"><span>Total pagado</span><strong id="detailTotal"></strong></div>
                    </div>
                </div>
            </div>
            <div class="modal-actions flex-wrap"><button type="button" data-close-detail class="admin-secondary-button">Cerrar</button><button type="button" id="shareSale" class="admin-secondary-button">Compartir PDF</button><button type="button" id="printSale" class="admin-primary-button">Imprimir</button></div>
            <div id="detailAlert" class="form-alert mt-4 hidden"></div>
        </div>
    </div>

    <div id="pdfLoader" class="sale-loader hidden" aria-live="polite">
        <div class="sale-loader-card"><span class="sale-spinner"></span><strong id="pdfLoaderTitle">Preparando PDF…</strong>
            <p>El archivo se procesa de forma segura en memoria.</p>
        </div>
    </div>

    <script src="https://cdn.datatables.net/3.0.4/js/dataTables.js"></script>
    <script>
        const empresaId = <?= $empresaId ?>,
            modal = document.getElementById('saleDetailModal'),
            money = new Intl.NumberFormat('es-PE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        let currentSale = null,
            currentPdfCache = {};
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const d = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', d);
            localStorage.setItem('landing-theme', d ? 'dark' : 'light')
        });
        const escapeHtml = value => {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML
        };

        function closeDetail() {
            modal.classList.remove('flex');
            document.body.style.overflow = ''
        }
        document.querySelectorAll('[data-close-detail]').forEach(button => button.addEventListener('click', closeDetail));

        function detailMessage(state, message) {
            const el = document.getElementById('detailAlert');
            el.dataset.state = state;
            el.textContent = message;
            el.classList.remove('hidden')
        }
        async function fetchJson(url, timeout = 20000) {
            const controller = new AbortController(),
                timer = setTimeout(() => controller.abort(), timeout);
            try {
                const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json'
                        },
                        signal: controller.signal
                    }),
                    json = await response.json();
                if (!response.ok || !json.success) throw new Error(json.error?.message || 'No se pudo cargar la venta.');
                return json.data
            } finally {
                clearTimeout(timer)
            }
        }
        document.querySelectorAll('[data-view-sale]').forEach(button => button.addEventListener('click', async () => {
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            document.getElementById('detailLoading').classList.remove('hidden');
            document.getElementById('detailContent').classList.add('hidden');
            document.getElementById('detailAlert').classList.add('hidden');
            currentSale = null;
            currentPdfCache = {};
            try {
                const sale = await fetchJson(`/api/facturacion/pos/ventas/${button.dataset.viewSale}?empresa_id=${empresaId}`);
                currentSale = sale;
                const sign = sale.moneda === 'USD' ? '$' : 'S/';
                document.getElementById('detailTitle').textContent = sale.numero;
                document.getElementById('detailSubtitle').textContent = `${sale.cliente.nombre} · ${sale.cliente.numero_documento} · ${sale.fecha_emision}`;
                document.getElementById('detailSummary').innerHTML = `<div class="sale-detail-card"><span>Cliente</span><strong>${escapeHtml(sale.cliente.nombre)}</strong><small>${escapeHtml(sale.cliente.direccion||'Sin dirección')}</small></div><div class="sale-detail-card"><span>Documento</span><strong>${escapeHtml(sale.cliente.numero_documento)}</strong><small>${sale.tipo==='01'?'Factura':'Boleta'} · ${escapeHtml(sale.estado)}</small></div><div class="sale-detail-card"><span>Total pagado</span><strong>${sign} ${money.format(sale.total)}</strong><small>IGV ${sign} ${money.format(sale.igv)}</small></div>`;
                document.getElementById('detailItems').innerHTML = sale.detalles.map(item => `<tr class="border-b border-slate-100 dark:border-white/5"><td class="px-3 py-3"><strong class="block">${escapeHtml(item.descripcion)}</strong><small class="text-slate-400">${escapeHtml(item.codigo||'')} · ${escapeHtml(item.unidad)}</small></td><td class="px-3 py-3 text-right">${item.cantidad}</td><td class="px-3 py-3 text-right">${sign} ${money.format(item.precio_unitario)}</td><td class="px-3 py-3 text-right">${sign} ${money.format(item.igv)}</td><td class="px-3 py-3 text-right font-semibold">${sign} ${money.format(item.total)}</td></tr>`).join('');
                document.getElementById('detailSubtotal').textContent = `${sign} ${money.format(sale.subtotal)}`;
                document.getElementById('detailIgv').textContent = `${sign} ${money.format(sale.igv)}`;
                document.getElementById('detailTotal').textContent = `${sign} ${money.format(sale.total)}`;
                document.getElementById('printSale').disabled = !sale.pdf_disponible;
                document.getElementById('shareSale').disabled = true;
                document.getElementById('detailLoading').classList.add('hidden');
                document.getElementById('detailContent').classList.remove('hidden');
                if (sale.pdf_disponible) getPdfBlob('a4').then(() => {
                    document.getElementById('shareSale').disabled = false
                }).catch(() => {})
            } catch (error) {
                document.getElementById('detailLoading').classList.add('hidden');
                detailMessage('error', error.message)
            }
        }));

        function togglePdfLoader(show, title = 'Preparando PDF…') {
            document.getElementById('pdfLoaderTitle').textContent = title;
            document.getElementById('pdfLoader').classList.toggle('hidden', !show)
        }
        async function getPdfBlob(format) {
            if (currentPdfCache[format]) return currentPdfCache[format];
            if (!currentSale) throw new Error('No hay una venta seleccionada.');
            const controller = new AbortController(),
                timer = setTimeout(() => controller.abort(), 30000);
            try {
                const response = await fetch(`/api/facturacion/pos/comprobantes/${currentSale.id}/pdf?empresa_id=${empresaId}&formato=${format}`, {
                    signal: controller.signal
                });
                if (!response.ok) {
                    let message = 'PDF no disponible.';
                    try {
                        message = (await response.json()).error?.message || message
                    } catch (_) {}
                    throw new Error(message)
                }
                const blob = await response.blob();
                currentPdfCache[format] = blob;
                return blob
            } finally {
                clearTimeout(timer)
            }
        }
        async function printCurrent() {
            const popup = window.open('', 'impresion-comprobante', 'popup=yes,width=430,height=720,toolbar=no,location=no,menubar=no,status=no,resizable=yes,scrollbars=no');
            if (!popup) {
                detailMessage('error', 'El navegador bloqueó la ventana de impresión. Habilita ventanas emergentes e inténtalo nuevamente.');
                return
            }
            popup.document.write('<title>Preparando impresión</title><body style="font-family:sans-serif;display:grid;place-items:center;height:90vh">Preparando impresión…</body>');
            togglePdfLoader(true, 'Preparando impresión…');
            try {
                const blob = await getPdfBlob('ticket'),
                    url = URL.createObjectURL(blob);
                popup.location.replace(url);
                setTimeout(() => {
                    try {
                        popup.focus();
                        popup.print()
                    } finally {
                        setTimeout(() => URL.revokeObjectURL(url), 60000)
                    }
                }, 1200)
            } catch (error) {
                popup.close();
                detailMessage('error', error.name === 'AbortError' ? 'La descarga del PDF excedió el tiempo de espera.' : error.message)
            } finally {
                togglePdfLoader(false)
            }
        }
        async function shareCurrent() {
            togglePdfLoader(true, 'Preparando archivo para compartir…');
            try {
                const blob = await getPdfBlob('a4'),
                    file = new File([blob], `${currentSale.numero}.pdf`, {
                        type: 'application/pdf'
                    });
                if (!navigator.share || !navigator.canShare?.({
                        files: [file]
                    })) throw new Error('Este navegador no permite compartir archivos. Abre esta página desde un celular compatible.');
                await navigator.share({
                    files: [file],
                    title: `Comprobante ${currentSale.numero}`,
                    text: 'Comprobante electrónico'
                });
                detailMessage('success', 'PDF compartido correctamente.')
            } catch (error) {
                if (error.name !== 'AbortError' && error.name !== 'NotAllowedError') detailMessage('error', error.message);
                if (error.name === 'AbortError') detailMessage('error', 'La descarga del PDF excedió el tiempo de espera.')
            } finally {
                togglePdfLoader(false)
            }
        }
        document.getElementById('printSale').addEventListener('click', printCurrent);
        document.getElementById('shareSale').addEventListener('click', shareCurrent);
        if (window.DataTable && document.getElementById('ventasTable')) new DataTable('#ventasTable', {
            pageLength: 10,
            lengthChange: false,
            order: [
                [2, 'desc']
            ],
            columnDefs: [{
                orderable: false,
                searchable: false,
                targets: 6
            }],
            language: {
                search: 'Buscar:',
                searchPlaceholder: 'Cliente o comprobante…',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ ventas',
                infoEmpty: 'Sin ventas',
                zeroRecords: 'No se encontraron ventas',
                emptyTable: 'No hay ventas emitidas',
                paginate: {
                    previous: 'Anterior',
                    next: 'Siguiente'
                }
            }
        });
    </script>
</body>

</html>