<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$baseDir = str_replace('/index.php', '', $scriptName);
$baseUrl = $protocol . '://' . $host . $baseDir;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentación API Facturación Electrónica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: #343a40; color: #fff; min-height: 100vh; padding: 20px; position: fixed; width: 250px; overflow-y: auto; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 10px 0; border-bottom: 1px solid #495057; }
        .sidebar a:hover, .sidebar a.active { color: #fff; }
        .content { margin-left: 250px; padding: 30px; }
        pre { background: #2b2b2b; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 14px; }
        .method { font-weight: bold; padding: 4px 8px; border-radius: 4px; color: white; display: inline-block; font-size: 12px; margin-right: 5px; }
        .method.get { background-color: #0d6efd; }
        .method.post { background-color: #198754; }
        .method.put { background-color: #fd7e14; }
        .method.delete { background-color: #dc3545; }
        .endpoint-url { font-family: monospace; font-size: 16px; font-weight: bold; }
        .card { margin-bottom: 30px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .card-header { background-color: #fff; border-bottom: 2px solid #f0f0f0; padding: 15px 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="mb-4 text-white">API SUNAT Perú</h4>
    <a href="#intro">Introducción</a>
    <a href="#errores">Manejo de Errores</a>
    <a href="#ejemplos">Ejemplos de Integración</a>
    
    <div class="mt-4 mb-2 text-uppercase" style="font-size: 12px; font-weight: bold; color: #6c757d;">Empresas</div>
    <a href="#empresa-crear">Registrar Empresa</a>
    <a href="#empresa-editar">Editar Empresa</a>
    <a href="#empresa-certificado">Subir Certificado</a>
    <a href="#empresa-logo">Subir Logo</a>
    <a href="#empresa-series">Crear Serie</a>
    
    <div class="mt-4 mb-2 text-uppercase" style="font-size: 12px; font-weight: bold; color: #6c757d;">Comprobantes</div>
    <a href="#comprobante-emitir">Emitir Comprobante</a>
    <a href="#comprobante-descargar">Descargar Archivos</a>
    <a href="#comprobante-estado">Consultar Estado</a>
</div>

<div class="content">
    <section id="intro" class="mb-5">
        <h1 class="display-4">Documentación API de Facturación</h1>
        <p class="lead">Bienvenido a la documentación de la API de Facturación Electrónica SUNAT.</p>
        <div class="alert alert-primary">
            <strong>Autenticación:</strong> Todas las llamadas a los endpoints de la API requieren de un <code>token</code> de acceso único asociado a su empresa.<br>
            Puede enviar el token de dos formas:
            <ul>
                <li><strong>GET (URL param):</strong> Añada <code>?token=YOUR_TOKEN</code> al final de la URL.</li>
                <li><strong>POST/PUT (JSON body):</strong> Añada <code>"token": "YOUR_TOKEN"</code> dentro del cuerpo del JSON de la solicitud.</li>
            </ul>
        </div>
    </section>

    <section id="errores" class="mb-5">
        <h2>Manejo de Errores</h2>
        <p>La API devuelve respuestas en formato JSON con la siguiente estructura base:</p>
        <pre>{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Mensaje de error descriptivo",
        "details": []
    }
}</pre>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr><th>Código HTTP</th><th>Código Interno</th><th>Descripción</th></tr>
            </thead>
            <tbody>
                <tr><td>400</td><td><code>VALIDATION_ERROR</code></td><td>Faltan campos obligatorios o el formato de datos es incorrecto.</td></tr>
                <tr><td>404</td><td><code>NOT_FOUND</code></td><td>El recurso (empresa, comprobante, etc) no existe.</td></tr>
                <tr><td>502</td><td><code>SUNAT_ERROR</code></td><td>Rechazo o error devuelto directamente por la SUNAT (código CDR).</td></tr>
                <tr><td>500</td><td><code>INTERNAL_ERROR</code></td><td>Error en el servidor o caída de la base de datos.</td></tr>
            </tbody>
        </table>
    </section>

    <section id="ejemplos" class="mb-5">
        <h2>Ejemplos de Integración</h2>
        <p>Aquí tienes ejemplos prácticos para consumir la API desde distintos lenguajes de programación. El endpoint usado de ejemplo es el de "Emitir Comprobante".</p>
        
        <ul class="nav nav-tabs" id="codeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="js-tab" data-bs-toggle="tab" data-bs-target="#js-code" type="button" role="tab">JavaScript (Fetch)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="php-tab" data-bs-toggle="tab" data-bs-target="#php-code" type="button" role="tab">PHP (cURL)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="python-tab" data-bs-toggle="tab" data-bs-target="#python-code" type="button" role="tab">Python (Requests)</button>
            </li>
        </ul>
        
        <div class="tab-content border border-top-0 p-3 rounded-bottom" id="codeTabsContent" style="background-color: #2b2b2b;">
            <!-- Tab JS -->
            <div class="tab-pane fade show active" id="js-code" role="tabpanel">
<pre class="m-0 border-0" style="background: transparent;"><code>const url = "<?php echo $baseUrl; ?>/api/facturacion/comprobantes";
const data = {
    token: "TU_TOKEN_AQUI",
    tipo_comprobante: "03",
    serie: "B001",
    cliente: { tipo_documento: "1", numero_documento: "12345678", nombre: "Juan Perez" },
    items: [ { descripcion: "Laptop", cantidad: 1, precio_unitario: 1500.00, afectacion_igv: "10" } ]
};

fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));</code></pre>
            </div>
            
            <!-- Tab PHP -->
            <div class="tab-pane fade" id="php-code" role="tabpanel">
<pre class="m-0 border-0" style="background: transparent;"><code>&lt;?php
$url = "<?php echo $baseUrl; ?>/api/facturacion/comprobantes";
$data = [
    "token" => "TU_TOKEN_AQUI",
    "tipo_comprobante" => "03",
    "serie" => "B001",
    "cliente" => [ "tipo_documento" => "1", "numero_documento" => "12345678", "nombre" => "Juan Perez" ],
    "items" => [ [ "descripcion" => "Laptop", "cantidad" => 1, "precio_unitario" => 1500.00, "afectacion_igv" => "10" ] ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);
echo $response;
?&gt;</code></pre>
            </div>
            
            <!-- Tab Python -->
            <div class="tab-pane fade" id="python-code" role="tabpanel">
<pre class="m-0 border-0" style="background: transparent;"><code>import requests

url = "<?php echo $baseUrl; ?>/api/facturacion/comprobantes"
data = {
    "token": "TU_TOKEN_AQUI",
    "tipo_comprobante": "03",
    "serie": "B001",
    "cliente": { "tipo_documento": "1", "numero_documento": "12345678", "nombre": "Juan Perez" },
    "items": [ { "descripcion": "Laptop", "cantidad": 1, "precio_unitario": 1500.00, "afectacion_igv": "10" } ]
}

response = requests.post(url, json=data)
print(response.json())</code></pre>
            </div>
        </div>
    </section>

    <hr class="my-5">
    <h2 class="mb-4">Módulo de Empresas <span class="badge bg-warning text-dark fs-6 ms-2">Solo Administración</span></h2>
    <div class="alert alert-secondary">
        <strong>Nota:</strong> Estos endpoints de gestión de empresas son de uso administrativo. Los clientes finales gestionan sus empresas visualmente desde el Dashboard web.
    </div>

    <!-- POST Empresa -->
    <div class="card" id="empresa-crear">
        <div class="card-header">
            <span class="method post">POST</span>
            <span class="endpoint-url">/api/facturacion/empresas</span>
        </div>
        <div class="card-body">
            <p>Registra una nueva empresa en el sistema de facturación.</p>
            <h5>Body (JSON)</h5>
            <pre>{
    "ruc": "20467534026",
    "razon_social": "AMERICA MOVIL PERU S.A.C.",
    "nombre_comercial": "Claro",
    "direccion": "AV BARRANCA - LIMA",
    "ubigeo": "150101",
    "departamento": "LIMA",
    "provincia": "LIMA",
    "distrito": "LIMA",
    "sol_usuario": "MODDATOS",
    "sol_password": "moddatos",
    "entorno": "beta"
}</pre>
            <p class="text-muted"><small>* <strong>entorno</strong>: Puede ser <code>beta</code> (homologación/pruebas) o <code>produccion</code> (SUNAT real).</small></p>
            <h5>Respuesta Exitosa (200 OK)</h5>
            <pre>{
    "success": true,
    "data": {
        "id": 1,
        "message": "Empresa registrada correctamente"
    }
}</pre>
        </div>
    </div>

    <!-- PUT Empresa -->
    <div class="card" id="empresa-editar">
        <div class="card-header">
            <span class="method put">PUT</span>
            <span class="endpoint-url">/api/facturacion/empresas/{id}</span>
        </div>
        <div class="card-body">
            <p>Edita los datos de una empresa existente.</p>
            <h5>Body (JSON) - Todos los campos son opcionales</h5>
            <pre>{
    "direccion": "NUEVA DIRECCION 123",
    "sol_password": "nuevopassword",
    "entorno": "produccion"
}</pre>
        </div>
    </div>

    <!-- POST Certificado -->
    <div class="card" id="empresa-certificado">
        <div class="card-header">
            <span class="method post">POST</span>
            <span class="endpoint-url">/api/facturacion/empresas/{id}/certificado</span>
        </div>
        <div class="card-body">
            <p>Sube el certificado digital (`.p12` o `.pfx`) de la empresa.</p>
            <h5>Body (multipart/form-data)</h5>
            <table class="table">
                <tr><th>Key</th><th>Tipo</th><th>Descripción</th></tr>
                <tr><td><code>certificado</code></td><td>File</td><td>Archivo .p12 o .pfx</td></tr>
            </table>
        </div>
    </div>

    <!-- POST Logo -->
    <div class="card" id="empresa-logo">
        <div class="card-header">
            <span class="method post">POST</span>
            <span class="endpoint-url">/api/facturacion/empresas/{id}/logo</span>
        </div>
        <div class="card-body">
            <p>Sube el logotipo de la empresa para usarlo en los comprobantes PDF.</p>
            <h5>Body (multipart/form-data)</h5>
            <table class="table">
                <tr><th>Key</th><th>Tipo</th><th>Descripción</th></tr>
                <tr><td><code>logo</code></td><td>File</td><td>Archivo de imagen (JPG, PNG o SVG)</td></tr>
            </table>
        </div>
    </div>

    <!-- POST Series -->
    <div class="card" id="empresa-series">
        <div class="card-header">
            <span class="method post">POST</span>
            <span class="endpoint-url">/api/facturacion/empresas/{id}/series</span>
        </div>
        <div class="card-body">
            <p>Registra una serie para emitir un tipo de comprobante.</p>
            <h5>Body (JSON)</h5>
            <pre>{
    "tipo_comprobante": "03", // 01 Factura, 03 Boleta, 07 Nota Crédito, 08 Nota Débito
    "serie": "B001",
    "correlativo_actual": 0
}</pre>
        </div>
    </div>

    <hr class="my-5">
    <h2 class="mb-4">Módulo de Comprobantes</h2>

    <!-- POST Emitir -->
    <div class="card" id="comprobante-emitir">
        <div class="card-header">
            <span class="method post">POST</span>
            <span class="endpoint-url">/api/facturacion/comprobantes</span>
        </div>
        <div class="card-body">
            <p>Crea, firma, envía a SUNAT y genera los PDFs de un nuevo comprobante.</p>
            <h5>Body (JSON)</h5>
            <pre>{
    "token": "TU_TOKEN_AQUI",
    "tipo_comprobante": "03",
    "serie": "B001",
    "moneda": "PEN",
    "fecha_emision": "2026-09-11", // Opcional (toma hoy por defecto)
    "cliente": {
        "tipo_documento": "1", // 1=DNI, 6=RUC, 4=CE, 7=Pasaporte
        "numero_documento": "12345678",
        "nombre": "Juan Perez",
        "direccion": "Av. Lima 123"
    },
    "items": [
        {
            "codigo": "P001",
            "descripcion": "Laptop Gamer",
            "unidad": "NIU", // Unidad de medida (NIU=Unidades, ZZ=Servicios)
            "cantidad": 1,
            "precio_unitario": 2500.00, // Precio con IGV incluido (si afectacion es 10)
            "afectacion_igv": "10" // 10=Gravado, 20=Exonerado, 30=Inafecto
        }
    ]
}</pre>
            <h5>Respuesta Exitosa (200 OK)</h5>
            <pre>{
    "success": true,
    "data": {
        "id": 12,
        "tipo": "03",
        "serie": "B001",
        "correlativo": "00000012",
        "numero": "B001-00000012",
        "estado": "aceptado",
        "hash": "abc123xyz...",
        "xml_path": "storage/private/facturacion/20467534026/2026/09/xml/...",
        "pdf_path": "storage/private/facturacion/20467534026/2026/09/pdf/...",
        "cdr_path": "storage/private/facturacion/20467534026/2026/09/cdr/...",
        "mensaje": "La Boleta numero B001-00000012, ha sido aceptada"
    }
}</pre>
        </div>
    </div>

    <!-- GET Descargas -->
    <div class="card" id="comprobante-descargar">
        <div class="card-header">
            <span class="method get">GET</span>
            <span class="endpoint-url">/api/facturacion/comprobantes/{id}/[pdf|xml|cdr]</span>
        </div>
        <div class="card-body">
            <p>Permite descargar directamente los archivos físicos del comprobante. Al ser peticiones GET, el token debe ir en la URL.</p>
            <ul>
                <li><code>/pdf?token=YOUR_TOKEN</code>: Descarga el PDF (Por defecto tamaño A4).</li>
                <li><code>/pdf?formato=ticket&token=YOUR_TOKEN</code>: Descarga el PDF en formato Ticket Térmico de 80mm.</li>
                <li><code>/xml?token=YOUR_TOKEN</code>: Descarga el archivo XML firmado.</li>
                <li><code>/cdr?token=YOUR_TOKEN</code>: Descarga el CDR (Constancia de Recepción) en ZIP devuelto por la SUNAT.</li>
            </ul>
        </div>
    </div>

    <!-- GET Estado -->
    <div class="card" id="comprobante-estado">
        <div class="card-header">
            <span class="method get">GET</span>
            <span class="endpoint-url">/api/facturacion/comprobantes/{id}/estado</span>
        </div>
        <div class="card-body">
            <p>Consulta el estado actual de un comprobante que ya fue enviado o quedó en estado "generando" o "error". Vuelve a consultar a la SUNAT si es necesario.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
