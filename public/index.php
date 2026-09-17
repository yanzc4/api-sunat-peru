<?php

declare(strict_types=1);

// =====================================================
// Entry Point alternativo (document root = public/)
// Mismo comportamiento que index.php de la raíz.
// La sesión web se mantiene con JWT (cookie firmada HttpOnly).
// =====================================================

require_once __DIR__ . '/../app/Facturacion/bootstrap.php';

// Iniciar Flight
Flight::start();
