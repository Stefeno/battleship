<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_GET['game_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falta el ID del juego']);
    exit;
}

$gameId = (int)$_GET['game_id'];
$lastUpdate = isset($_GET['last_update']) ? (int)$_GET['last_update'] : 0;

try {
    // Obtener mensajes nuevos
    $stmt = $pdo->prepare("
        SELECT m.*, u.username 
        FROM chat_messages m 
        JOIN users u ON m.player_id = u.id 
        WHERE m.game_id = ? AND m.created_at > FROM_UNIXTIME(?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$gameId, $lastUpdate]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener la última actualización
    $stmt = $pdo->prepare("
        SELECT UNIX_TIMESTAMP(MAX(created_at)) as last_update 
        FROM chat_messages 
        WHERE game_id = ?
    ");
    $stmt->execute([$gameId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $lastUpdate = $result['last_update'] ?? 0;

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'last_update' => $lastUpdate
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor' . $e ]);
}
?> 