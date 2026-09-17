<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Facturacion\Controllers\EmpresaController;
use App\Facturacion\Controllers\FacturacionController;
use App\Facturacion\Config\Database;
use App\Facturacion\Helpers\AuthGuard;
use App\Facturacion\Helpers\ApiAuthPolicy;
use App\Facturacion\Repositories\ApiTokenRepository;
use App\Facturacion\Helpers\ResponseHelper;
use App\Facturacion\Services\JwtService;

// =====================================================
// Políticas de autenticación separadas
// =====================================================
// - /api/facturacion/comprobantes/**: solo api_token empresarial.
// - /api/facturacion/empresas/**: solo sesión JWT del dashboard.
Flight::before('start', function() {
    $req = Flight::request();
    $url = $req->url;

    $policy = ApiAuthPolicy::forPath($url);

    if ($policy === ApiAuthPolicy::API_TOKEN) {
        // El JWT nunca sustituye al token empresarial en la API pública.
        $token = ApiAuthPolicy::extractApiToken(
            $req->method,
            ['token' => $req->query->token ?? null],
            $req->getBody()
        );

        if ($token === '') {
            ResponseHelper::unauthorized('Token empresarial requerido');
        }

        $repo = new ApiTokenRepository(Database::getConnection());
        $apiToken = $repo->findByToken($token);

        if (!$apiToken) {
            ResponseHelper::unauthorized('Token empresarial inválido o inactivo');
        }

        Flight::set('auth_empresa_id', $apiToken->empresaId);
        return;
    }

    if ($policy === ApiAuthPolicy::JWT) {
        if (AuthGuard::resolveUser() === null) {
            ResponseHelper::unauthorized('Sesión de dashboard requerida');
        }
    }
});

// =====================================================
// Rutas de Empresas
// =====================================================

Flight::route('GET /api/facturacion/empresas', function () {
    $controller = new EmpresaController();
    $controller->listar();
});

Flight::route('GET /api/facturacion/empresas/@id', function (string $id) {
    $controller = new EmpresaController();
    $controller->ver($id);
});

Flight::route('POST /api/facturacion/empresas', function () {
    $controller = new EmpresaController();
    $controller->crear();
});

Flight::route('PUT /api/facturacion/empresas/@id', function (string $id) {
    $controller = new EmpresaController();
    $controller->editar($id);
});

Flight::route('DELETE /api/facturacion/empresas/@id', function (string $id) {
    $controller = new EmpresaController();
    $controller->eliminar($id);
});

Flight::route('POST /api/facturacion/empresas/@id/certificado', function (string $id) {
    $controller = new EmpresaController();
    $controller->subirCertificado($id);
});

Flight::route('POST /api/facturacion/empresas/@id/logo', function (string $id) {
    $controller = new EmpresaController();
    $controller->subirLogo($id);
});

Flight::route('POST /api/facturacion/empresas/@id/series', function (string $id) {
    $controller = new EmpresaController();
    $controller->crearSerie($id);
});

// =====================================================
// Rutas de Comprobantes
// =====================================================

Flight::route('GET /api/facturacion/comprobantes', function () {
    $controller = new FacturacionController();
    $controller->listar();
});

Flight::route('GET /api/facturacion/comprobantes/@id', function (string $id) {
    $controller = new FacturacionController();
    $controller->ver($id);
});

Flight::route('POST /api/facturacion/comprobantes', function () {
    $controller = new FacturacionController();
    $controller->emitir();
});

Flight::route('POST /api/facturacion/comprobantes/@id/procesar', function (string $id) {
    $controller = new FacturacionController();
    $controller->procesar($id);
});

Flight::route('GET /api/facturacion/comprobantes/@id/estado', function (string $id) {
    $controller = new FacturacionController();
    $controller->consultarEstado($id);
});

Flight::route('GET /api/facturacion/comprobantes/@id/pdf', function (string $id) {
    $controller = new FacturacionController();
    $controller->descargarPdf($id);
});

Flight::route('GET /api/facturacion/comprobantes/@id/xml', function (string $id) {
    $controller = new FacturacionController();
    $controller->descargarXml($id);
});

Flight::route('GET /api/facturacion/comprobantes/@id/cdr', function (string $id) {
    $controller = new FacturacionController();
    $controller->descargarCdr($id);
});

// =====================================================
// Ruta Raíz y Documentación
// =====================================================
// Autenticación (JWT)
// =====================================================

Flight::route('GET /login', function () {
    // Si ya tiene sesión válida, directo al dashboard
    if (AuthGuard::resolveUser() !== null) {
        header('Location: /dashboard');
        exit;
    }
    require dirname(__DIR__) . '/views/auth/login.php';
});

Flight::route('POST /login', function () {
    $req = Flight::request();
    $email = trim((string) ($req->data['email'] ?? $req->data->email ?? ''));
    $password = trim((string) ($req->data['password'] ?? $req->data->password ?? ''));

    $repo = new \App\Facturacion\Repositories\UsuarioRepository(Database::getConnection());
    $usuario = $repo->findByEmail($email);

    if (!$usuario || !password_verify($password, $usuario->password)) {
        // Cliente API (JSON) → 401; navegador → redirect con error
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isJson = str_contains($req->type ?? '', 'application/json') || str_contains($accept, 'application/json');
        if ($isJson) {
            ResponseHelper::unauthorized('Credenciales incorrectas');
        }
        header('Location: /login?error=1');
        exit;
    }

    $jwt = (new JwtService())->generate($usuario);

    // Cliente API (JSON) → token en el body
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isJson = str_contains($req->type ?? '', 'application/json') || str_contains($accept, 'application/json');
    if ($isJson) {
        ResponseHelper::success([
            'token' => $jwt['token'],
            'token_type' => 'Bearer',
            'expires_at' => $jwt['expires_at'],
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
            ],
        ]);
    }

    // Navegador → cookie firmada HttpOnly y redirect al dashboard
    AuthGuard::setAuthCookie($jwt['token'], $jwt['expires_at']);
    header('Location: /dashboard');
    exit;
});

Flight::route('GET /logout', function () {
    AuthGuard::clearAuthCookie();
    header('Location: /');
    exit;
});

// =====================================================
// Dashboard (protegido con JWT)
// =====================================================

Flight::route('GET /dashboard', function () {
    $usuario = AuthGuard::requireWebUser();
    require dirname(__DIR__) . '/views/dashboard/index.php';
});

Flight::route('POST /dashboard', function () {
    AuthGuard::requireAdmin();

    if (isset($_POST['action']) && $_POST['action'] === 'crear_token') {
        $emp_id = (int)$_POST['empresa_id'];
        $db = Database::getConnection();
        $tokenRepo = new \App\Facturacion\Repositories\ApiTokenRepository($db);
        $tokenRepo->createToken($emp_id);
    }
    header('Location: /dashboard');
    exit;
});

// =====================================================

Flight::route('GET /', function () {
    $docsPath = dirname(__DIR__) . '/views/landing/index.php';
    if (file_exists($docsPath)) {
        require $docsPath;
    } else {
        Flight::json(['message' => 'API Facturación SUNAT v1.0 - SaaS']);
    }
});

Flight::route('GET /doc', function () {
    $docsPath = dirname(__DIR__) . '/views/docs/index.php';
    if (file_exists($docsPath)) {
        require $docsPath;
    } else {
        Flight::notFound();
    }
});

Flight::route('GET /contactar', function () {
    $contactPath = dirname(__DIR__) . '/views/contact/index.php';
    if (file_exists($contactPath)) {
        require $contactPath;
    } else {
        Flight::notFound();
    }
});

Flight::route('POST /request-access', function () {
    $req = Flight::request();
    $nombre = $req->data->nombre;
    $email = $req->data->email;
    $empresa = $req->data->empresa;
    $ruc = $req->data->ruc;
    $telefonoUsuario = $req->data->telefono;

    $mensaje = "NUEVA SOLICITUD DE ACCESO API\nNombre: {$nombre}\nEmail: {$email}\nTel: {$telefonoUsuario}\nEmpresa: {$empresa} (RUC: {$ruc})";
    
    $telefonoDestino = '51979829261';
    $apikey = '1446417';
    $url = "https://api.callmebot.com/whatsapp.php?" . http_build_query([
        'phone'  => $telefonoDestino,
        'text'   => $mensaje,
        'apikey' => $apikey
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log('Error CallMeBot: ' . $error);
        Flight::json([
            'success' => false,
            'message' => 'Error al contactar por WhatsApp. Escribe directamente.'
        ]);
    }else{
        Flight::json([
            'success' => true,
            'message' => '¡Solicitud enviada! Nos contactaremos contigo pronto.'
        ]);
    }
});

// =====================================================
// Ruta 404
// =====================================================

Flight::map('notFound', function () {
    Flight::json([
        'success' => false,
        'error' => [
            'code' => 'NOT_FOUND',
            'message' => 'Ruta no encontrada',
        ],
    ], 404);
});

// =====================================================
// Ruta error
// =====================================================

Flight::map('error', function (Throwable $e) {
    ResponseHelper::internalException($e, 'Error no controlado en Flight');
});
