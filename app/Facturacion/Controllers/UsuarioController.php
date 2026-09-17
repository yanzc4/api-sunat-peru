<?php

declare(strict_types=1);

namespace App\Facturacion\Controllers;

use App\Facturacion\Config\Database;
use App\Facturacion\Helpers\AuthContext;
use App\Facturacion\Helpers\ResponseHelper;
use App\Facturacion\Repositories\UsuarioRepository;

final class UsuarioController
{
    private UsuarioRepository $repo;

    public function __construct()
    {
        $this->repo = new UsuarioRepository(Database::getConnection());
    }

    public function crear(): void
    {
        try {
            if (!AuthContext::isAdmin()) {
                ResponseHelper::forbidden('Solo el administrador puede crear usuarios.');
            }

            $data = json_decode((string) file_get_contents('php://input'), true);
            if (!is_array($data)) {
                ResponseHelper::validationError('JSON inválido');
            }

            $nombre = trim((string) ($data['nombre'] ?? ''));
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $password = (string) ($data['password'] ?? '');
            $rol = trim((string) ($data['rol'] ?? 'cliente'));

            if ($nombre === '' || $email === '' || $password === '') {
                ResponseHelper::validationError('Nombre, correo y contraseña son obligatorios.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                ResponseHelper::validationError('El correo electrónico no es válido.');
            }
            if (strlen($password) < 8) {
                ResponseHelper::validationError('La contraseña debe tener al menos 8 caracteres.');
            }
            if (!in_array($rol, ['admin', 'cliente'], true)) {
                ResponseHelper::validationError('El rol seleccionado no es válido.');
            }
            if ($this->repo->findByEmail($email)) {
                ResponseHelper::error('DUPLICATE_EMAIL', 'El correo electrónico ya está registrado.', 422);
            }

            $id = $this->repo->create($nombre, $email, password_hash($password, PASSWORD_DEFAULT), $rol);
            ResponseHelper::success([
                'id' => $id,
                'nombre' => $nombre,
                'email' => $email,
                'rol' => $rol,
                'message' => 'Usuario creado correctamente.',
            ], 201);
        } catch (\Throwable $e) {
            ResponseHelper::internalException($e, 'Error al crear usuario');
        }
    }
}
