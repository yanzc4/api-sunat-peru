<?php

declare(strict_types=1);

// =====================================================
// Entry Point: API Facturación Electrónica SUNAT
// La sesión web se mantiene con JWT (cookie firmada HttpOnly).
// =====================================================

require_once __DIR__ . '/app/Facturacion/bootstrap.php';

// Iniciar Flight
Flight::start();
