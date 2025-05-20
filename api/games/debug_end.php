<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($_POST['game_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de juego no proporcionado']);
    exit;
}

$gameId = (int)$_POST['game_id'];
$userId = $_SESSION['user_id'];

try {
    // Obtener información del juego
    $stmt = $pdo->prepare("
        SELECT player1_id, player2_id 
        FROM games 
        WHERE id = ? AND status = 'playing'
    ");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        echo json_encode(['success' => false, 'message' => 'Juego no encontrado o ya terminado']);
        exit;
    }

    // Verificar que el usuario es parte del juego
    if ($game['player1_id'] != $userId && $game['player2_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'No eres parte de este juego']);
        exit;
    }

    // Terminar el juego y declarar al usuario como ganador
    $stmt = $pdo->prepare("
        UPDATE games
        SET status = 'finished',
            winner_id = ?,
            current_player = NULL
        WHERE id = ?
    ");
    $stmt->execute([$userId, $gameId]);

    echo json_encode([
        'success' => true,
        'message' => 'Juego terminado en modo debug',
        'winner_id' => $userId
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en el servidor '. $e]);
}
?> 