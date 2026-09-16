<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - API SUNAT Perú</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { max-width: 400px; width: 100%; border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .login-header { background: #343a40; color: white; text-align: center; padding: 20px; border-radius: 10px 10px 0 0; }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="login-header">
        <h4 class="mb-0">API SUNAT Perú</h4>
        <small>Portal para Desarrolladores</small>
    </div>
    <div class="card-body p-4">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center py-2">
                Credenciales incorrectas.
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" required placeholder="admin@admin.com">
            </div>
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Iniciar Sesión</button>
        </form>
        <div class="text-center">
            <a href="/" class="text-decoration-none text-muted">Volver al inicio</a>
        </div>
    </div>
</div>

</body>
</html>
