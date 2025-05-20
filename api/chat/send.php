<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['game_id']) || !isset($_POST['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

$gameId = (int)$_POST['game_id'];
$message = trim($_POST['message']);

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
    exit;
}

try {
    // Verificar que el juego existe y el usuario es parte de él
    $stmt = $pdo->prepare("
        SELECT * FROM games
        WHERE id = ? AND (player1_id = ? OR player2_id = ?)
    ");
    $stmt->execute([$gameId, $_SESSION['user_id'], $_SESSION['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Juego no encontrado o no eres parte de él']);
        exit;
    }

    // Insertar mensaje
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (game_id, player_id, message)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$gameId, $_SESSION['user_id'], $message]);

    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado exitosamente'
    ]);
} catch (PDOException $e) {
    error_log("Error en el servidor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 