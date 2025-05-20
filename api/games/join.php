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

if (!isset($_POST['game_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de juego no proporcionado']);
    exit;
}

$gameId = (int)$_POST['game_id'];

try {
    // Verificar que el juego existe y está esperando un segundo jugador
    $stmt = $pdo->prepare("
        SELECT * FROM games
        WHERE id = ? AND status = 'setup' AND player2_id IS NULL
    ");
    $stmt->execute([$gameId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Juego no encontrado o no está disponible para unirse']);
        exit;
    }

    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar que el usuario no es el creador del juego
    if ($game['player1_id'] == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No puedes unirte a tu propio juego']);
        exit;
    }

    // Unir al jugador al juego
    $stmt = $pdo->prepare("
        UPDATE games
        SET player2_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $gameId]);

    echo json_encode([
        'success' => true,
        'message' => 'Te has unido al juego exitosamente',
        'game_id' => $gameId
    ]);
} catch (PDOException $e) {
    error_log("Error en el servidor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 