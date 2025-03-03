<?php
// getFacturas.php
require_once '../config/database.php';
require_once '../models/Repositorio.php';

session_start();

// Verificar que el usuario esté logueado y que se tenga su nombre
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['nombre'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No tienes una sesión activa']);
    exit;
}

// Extraer el nombre del usuario
$userName = $_SESSION['user']['nombre'];

// Conexión a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Instanciar el modelo Repositorio
$repositorio = new Repositorio($conn);

// Obtener las facturas filtradas por el nombre del usuario
$facturas = $repositorio->getFacturasByUser($userName);

header('Content-Type: application/json');
echo json_encode($facturas);
?>
