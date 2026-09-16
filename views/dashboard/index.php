<?php
$usuarioId = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'];

$db = \App\Facturacion\Config\Database::getConnection();
$empresaRepo = new \App\Facturacion\Repositories\EmpresaFacturacionRepository($db);
$tokenRepo = new \App\Facturacion\Repositories\ApiTokenRepository($db);

// Si es admin puede ver todas y crear. Si es cliente, solo ve las suyas.
$empresas = ($rol === 'admin') 
    ? $empresaRepo->findAll(false) 
    : $empresaRepo->findByUsuarioId($usuarioId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - API SUNAT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard">Panel de Control</a>
    <div class="d-flex text-white align-items-center">
      <span class="me-3">Hola, <?= htmlspecialchars($nombre) ?> (<?= strtoupper($rol) ?>)</span>
      <a href="/logout" class="btn btn-sm btn-outline-light">Cerrar Sesión</a>
    </div>
  </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Mis Empresas</h2>
        <?php if ($rol === 'admin'): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrearEmpresa">+ Registrar Empresa</button>
        <?php endif; ?>
    </div>

    <?php if (empty($empresas)): ?>
        <div class="alert alert-warning">No tienes empresas registradas.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($empresas as $emp): 
                $apiToken = $tokenRepo->findByEmpresa($emp->id);
            ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-truncate" style="max-width: 70%;" title="<?= htmlspecialchars($emp->razonSocial) ?>">
                            <?= htmlspecialchars($emp->razonSocial) ?>
                        </h5>
                        <span class="badge bg-secondary">RUC: <?= $emp->ruc ?></span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <p class="mb-0"><strong>Entorno:</strong> <span class="badge bg-<?= $emp->entorno === 'produccion' ? 'success' : 'warning' ?>"><?= strtoupper($emp->entorno) ?></span></p>
                            <?php if ($emp->certificadoPath): ?>
                                <span class="badge bg-success text-white">Certificado OK</span>
                            <?php else: ?>
                                <span class="badge bg-danger text-white">Sin Certificado</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 p-3 bg-light border rounded">
                            <h6 class="text-muted">Token de Acceso API</h6>
                            <?php if ($apiToken): ?>
                                <code class="fs-6 d-block bg-white p-2 border"><?= htmlspecialchars($apiToken->token) ?></code>
                            <?php else: ?>
                                <p class="text-danger small mb-0">Sin token generado.</p>
                                <?php if ($rol === 'admin'): ?>
                                    <form method="POST" action="/dashboard">
                                        <input type="hidden" name="action" value="crear_token">
                                        <input type="hidden" name="empresa_id" value="<?= $emp->id ?>">
                                        <button type="submit" class="btn btn-sm btn-primary mt-2">Generar Token</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="btn-group w-100 mb-3" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="abrirModalCert(<?= $emp->id ?>)">+ Certificado</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="abrirModalLogo(<?= $emp->id ?>)">+ Logo</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="abrirModalSerie(<?= $emp->id ?>)">+ Serie</button>
                        </div>

                        <?php if ($apiToken): ?>
                            <a href="/doc#ejemplos" class="btn btn-sm btn-outline-info w-100">Ver Ejemplos de Integración</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($rol === 'admin'): ?>
<!-- Modales de Administración -->
<div class="modal fade" id="modalCrearEmpresa" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Registrar Nueva Empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCrearEmpresa">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>RUC</label>
                    <input type="text" name="ruc" class="form-control" required maxlength="11">
                </div>
                <div class="col-md-8 mb-3">
                    <label>Razón Social</label>
                    <input type="text" name="razon_social" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Usuario SOL</label>
                    <input type="text" name="sol_usuario" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Clave SOL</label>
                    <input type="password" name="sol_password" class="form-control" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Departamento</label>
                    <input type="text" name="departamento" class="form-control" value="LIMA">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Provincia</label>
                    <input type="text" name="provincia" class="form-control" value="LIMA">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Distrito</label>
                    <input type="text" name="distrito" class="form-control" value="LIMA">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Ubigeo</label>
                    <input type="text" name="ubigeo" class="form-control" value="150101">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Entorno</label>
                    <select name="entorno" class="form-select">
                        <option value="beta">BETA (Pruebas)</option>
                        <option value="produccion">PRODUCCIÓN</option>
                    </select>
                </div>
            </div>
            <!-- Como eres el único creando, se te asignará a tu usuario_id -->
        </form>
        <div id="resEmpresa" class="alert d-none mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" onclick="crearEmpresa()">Guardar Empresa</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Modales Públicos para Admin y Clientes -->
<div class="modal fade" id="modalCertificado" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Subir Certificado Digital (.p12 o .pfx)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCert">
            <input type="hidden" id="cert_empresa_id">
            <div class="mb-3">
                <label>Archivo de Certificado</label>
                <input type="file" class="form-control" id="cert_file" accept=".p12,.pfx" required>
            </div>
            <div class="mb-3">
                <label>Contraseña del Certificado</label>
                <input type="password" class="form-control" id="cert_pass" required>
            </div>
        </form>
        <div id="resCert" class="alert d-none mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="subirCert()">Subir</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalLogo" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Subir Logo de la Empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formLogo">
            <input type="hidden" id="logo_empresa_id">
            <div class="mb-3">
                <label>Archivo (JPG, PNG)</label>
                <input type="file" class="form-control" id="logo_file" accept=".jpg,.png,.jpeg,.svg" required>
            </div>
        </form>
        <div id="resLogo" class="alert d-none mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="subirLogo()">Subir</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalSerie" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Crear Serie (F001, B001)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formSerie">
            <input type="hidden" id="serie_empresa_id">
            <div class="mb-3">
                <label>Tipo Comprobante</label>
                <select class="form-select" id="serie_tipo">
                    <option value="01">Factura (01)</option>
                    <option value="03">Boleta (03)</option>
                    <option value="07">Nota Crédito (07)</option>
                    <option value="08">Nota Débito (08)</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Código de Serie (Ej: F001, B001)</label>
                <input type="text" class="form-control" id="serie_codigo" required maxlength="4">
            </div>
            <div class="mb-3">
                <label>Correlativo Inicial (Ej: 0)</label>
                <input type="number" class="form-control" id="serie_corr" value="0" required>
            </div>
        </form>
        <div id="resSerie" class="alert d-none mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="crearSerie()">Crear Serie</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const certModal = new bootstrap.Modal(document.getElementById('modalCertificado'));
    const logoModal = new bootstrap.Modal(document.getElementById('modalLogo'));
    const serieModal = new bootstrap.Modal(document.getElementById('modalSerie'));

    function abrirModalCert(id) { document.getElementById('cert_empresa_id').value = id; document.getElementById('resCert').className='d-none'; certModal.show(); }
    function abrirModalLogo(id) { document.getElementById('logo_empresa_id').value = id; document.getElementById('resLogo').className='d-none'; logoModal.show(); }
    function abrirModalSerie(id) { document.getElementById('serie_empresa_id').value = id; document.getElementById('resSerie').className='d-none'; serieModal.show(); }

    async function crearEmpresa() {
        const form = document.getElementById('formCrearEmpresa');
        if(!form.checkValidity()){ form.reportValidity(); return; }
        let data = Object.fromEntries(new FormData(form));
        let resDiv = document.getElementById('resEmpresa');
        
        try {
            resDiv.className = 'alert alert-info mt-2'; resDiv.innerText = 'Guardando...';
            let req = await fetch('/api/facturacion/empresas', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            let json = await req.json();
            if(json.success) { location.reload(); } else { resDiv.className='alert alert-danger mt-2'; resDiv.innerText = json.error.message || 'Error al guardar'; }
        } catch(e) { resDiv.className='alert alert-danger mt-2'; resDiv.innerText = 'Error de conexión'; }
    }

    async function subirCert() {
        let id = document.getElementById('cert_empresa_id').value;
        let pass = document.getElementById('cert_pass').value;
        let file = document.getElementById('cert_file').files[0];
        let resDiv = document.getElementById('resCert');
        
        if(!file || !pass) { alert("Completa los campos"); return; }
        
        let fd = new FormData();
        fd.append('certificado', file);
        fd.append('password', pass);

        try {
            resDiv.className = 'alert alert-info mt-2'; resDiv.innerText = 'Subiendo...';
            let req = await fetch(`/api/facturacion/empresas/${id}/certificado`, { method: 'POST', body: fd });
            let json = await req.json();
            if(json.success) { location.reload(); } else { resDiv.className='alert alert-danger mt-2'; resDiv.innerText = json.error.message || 'Error al subir'; }
        } catch(e) { resDiv.className='alert alert-danger mt-2'; resDiv.innerText = 'Error de conexión'; }
    }

    async function subirLogo() {
        let id = document.getElementById('logo_empresa_id').value;
        let file = document.getElementById('logo_file').files[0];
        let resDiv = document.getElementById('resLogo');
        
        if(!file) { alert("Selecciona un logo"); return; }
        
        let fd = new FormData(); fd.append('logo', file);
        try {
            resDiv.className = 'alert alert-info mt-2'; resDiv.innerText = 'Subiendo...';
            let req = await fetch(`/api/facturacion/empresas/${id}/logo`, { method: 'POST', body: fd });
            let json = await req.json();
            if(json.success) { location.reload(); } else { resDiv.className='alert alert-danger mt-2'; resDiv.innerText = json.error.message || 'Error al subir'; }
        } catch(e) { resDiv.className='alert alert-danger mt-2'; resDiv.innerText = 'Error de conexión'; }
    }

    async function crearSerie() {
        let id = document.getElementById('serie_empresa_id').value;
        let tipo = document.getElementById('serie_tipo').value;
        let serie = document.getElementById('serie_codigo').value;
        let corr = document.getElementById('serie_corr').value;
        let resDiv = document.getElementById('resSerie');
        
        if(!serie) { alert("Ingresa la serie"); return; }
        
        try {
            resDiv.className = 'alert alert-info mt-2'; resDiv.innerText = 'Creando...';
            let req = await fetch(`/api/facturacion/empresas/${id}/series`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ tipo_comprobante: tipo, serie: serie, correlativo_actual: parseInt(corr) })
            });
            let json = await req.json();
            if(json.success) { location.reload(); } else { resDiv.className='alert alert-danger mt-2'; resDiv.innerText = json.error.message || 'Error'; }
        } catch(e) { resDiv.className='alert alert-danger mt-2'; resDiv.innerText = 'Error de conexión'; }
    }
</script>

</body>
</html>
