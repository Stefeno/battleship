<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($_GET['game_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de juego no proporcionado']);
    exit;
}

$gameId = (int)$_GET['game_id'];

try {
    // Obtener información del juego
    $stmt = $pdo->prepare("
        SELECT g.*, u1.username as player1_name, u2.username as player2_name
        FROM games g
        LEFT JOIN users u1 ON g.player1_id = u1.id
        LEFT JOIN users u2 ON g.player2_id = u2.id
        WHERE g.id = ?
    ");
    $stmt->execute([$gameId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Juego no encontrado']);
        exit;
    }

    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario es parte del juego
    if ($game['player1_id'] != $_SESSION['user_id'] && $game['player2_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No eres parte de este juego']);
        exit;
    }

    // Obtener barcos colocados
    $stmt = $pdo->prepare("
        SELECT * FROM ships
        WHERE game_id = ? AND player_id = ?
    ");
    $stmt->execute([$gameId, $_SESSION['user_id']]);
    $ships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'game' => $game,
        'ships' => $ships
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 