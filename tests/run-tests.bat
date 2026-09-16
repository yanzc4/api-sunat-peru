@echo off
REM Ejecutar todos los tests unitarios
echo === Ejecutando tests unitarios ===
php tests\unit\StructureTest.php
php tests\unit\EncryptionServiceTest.php
php tests\unit\ComprobanteDTOTest.php
php tests\unit\ResponseHelperTest.php
php tests\security\SecurityCheck.php
echo.
echo === Todos los tests ejecutados ===
