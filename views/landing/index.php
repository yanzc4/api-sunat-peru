<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Facturación SUNAT Perú</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero { background: #343a40; color: white; padding: 80px 0; text-align: center; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/">API SUNAT</a>
    <div class="d-flex">
      <a href="/doc" class="btn btn-outline-secondary me-2">Ver Documentación</a>
      <a href="/login" class="btn btn-primary">Iniciar Sesión</a>
    </div>
  </div>
</nav>

<div class="hero">
    <div class="container">
        <h1 class="display-4 fw-bold">Facturación Electrónica Fácil</h1>
        <p class="lead mt-3">Envía comprobantes a SUNAT desde cualquier lenguaje con una simple petición REST.</p>
        <button class="btn btn-success btn-lg mt-4" data-bs-toggle="modal" data-bs-target="#requestModal">Solicitar Acceso</button>
    </div>
</div>

<div class="container py-5">
    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <h4>Simple</h4>
            <p class="text-muted">Olvídate de XML y SOAP. Envía JSON y nosotros hacemos el resto.</p>
        </div>
        <div class="col-md-4 mb-4">
            <h4>Seguro</h4>
            <p class="text-muted">Tus certificados digitales se almacenan cifrados en nuestro servidor.</p>
        </div>
        <div class="col-md-4 mb-4">
            <h4>Multiempresa</h4>
            <p class="text-muted">Gestiona decenas de empresas y RUCs desde un solo Panel de Control.</p>
        </div>
    </div>
</div>

<!-- Modal Solicitar Acceso -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Solicitar Acceso a la API</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="requestForm">
            <div class="mb-3">
                <label>Tu Nombre</label>
                <input type="text" class="form-control" name="nombre" required>
            </div>
            <div class="mb-3">
                <label>Correo Electrónico</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
                <label>Teléfono (WhatsApp)</label>
                <input type="text" class="form-control" name="telefono" required>
            </div>
            <div class="mb-3">
                <label>Razón Social (Empresa)</label>
                <input type="text" class="form-control" name="empresa" required>
            </div>
            <div class="mb-3">
                <label>RUC</label>
                <input type="text" class="form-control" name="ruc" required>
            </div>
        </form>
        <div id="requestAlert" class="alert d-none mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" onclick="sendRequest()">Enviar Solicitud</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function sendRequest() {
    const form = document.getElementById('requestForm');
    if(!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = Object.fromEntries(new FormData(form));
    const alertDiv = document.getElementById('requestAlert');
    alertDiv.className = 'alert alert-info mt-3';
    alertDiv.innerText = 'Enviando solicitud...';

    fetch('/request-access', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        alertDiv.className = 'alert mt-3 ' + (res.success ? 'alert-success' : 'alert-danger');
        alertDiv.innerText = res.message;
        if(res.success) form.reset();
    })
    .catch(err => {
        alertDiv.className = 'alert alert-danger mt-3';
        alertDiv.innerText = 'Error de conexión.';
    });
}
</script>
</body>
</html>
