<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

//$data = json_decode(file_get_contents('php://input'), true);
//
//if (!isset($data['username']) || !isset($data['password'])) {
//    http_response_code(400);
//    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
//    exit;
//}
//
//$username = $data['username'];
//$password = $data['password'];
// Instead of using json_decode(file_get_contents('php://input'))

if (!isset($_POST['username']) || !isset($_POST['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];


try {
    // Buscar usuario
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
        exit;
    }

    // Iniciar sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    echo json_encode([
        'success' => true,
        'message' => 'Inicio de sesión exitoso',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username']
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 