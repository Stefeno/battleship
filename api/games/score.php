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
$userId = $_SESSION['user_id'];

try {
    // Obtener información del juego
    $stmt = $pdo->prepare("
        SELECT player1_id, player2_id 
        FROM games 
        WHERE id = ?
    ");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Juego no encontrado']);
        exit;
    }

    // Determinar el ID del oponente
    $opponentId = $game['player1_id'] == $userId ? $game['player2_id'] : $game['player1_id'];

    // Contar los barcos del jugador que han sido hundidos por el oponente
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id) as sunk_ships
        FROM ships s
        WHERE s.game_id = ? AND s.player_id = ?
        AND EXISTS (
            SELECT 1
            FROM shots sh
            WHERE sh.game_id = s.game_id 
            AND sh.player_id = ?
            AND (
                (s.orientation = 'horizontal' AND sh.x >= s.start_x AND sh.x < s.start_x + s.size AND sh.y = s.start_y)
                OR
                (s.orientation = 'vertical' AND sh.x = s.start_x AND sh.y >= s.start_y AND sh.y < s.start_y + s.size)
            )
            GROUP BY s.id
            HAVING COUNT(*) = s.size
        )
    ");
    $stmt->execute([$gameId, $userId, $opponentId]);
    $playerSunkShips = (int)$stmt->fetch(PDO::FETCH_ASSOC)['sunk_ships'];

    // Contar los barcos del oponente que el jugador ha hundido
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id) as sunk_ships
        FROM ships s
        WHERE s.game_id = ? AND s.player_id = ?
        AND EXISTS (
            SELECT 1
            FROM shots sh
            WHERE sh.game_id = s.game_id 
            AND sh.player_id = ?
            AND (
                (s.orientation = 'horizontal' AND sh.x >= s.start_x AND sh.x < s.start_x + s.size AND sh.y = s.start_y)
                OR
                (s.orientation = 'vertical' AND sh.x = s.start_x AND sh.y >= s.start_y AND sh.y < s.start_y + s.size)
            )
            GROUP BY s.id
            HAVING COUNT(*) = s.size
        )
    ");
    $stmt->execute([$gameId, $opponentId, $userId]);
    $opponentSunkShips = (int)$stmt->fetch(PDO::FETCH_ASSOC)['sunk_ships'];

    // Devolver los valores desde la perspectiva del jugador actual
    echo json_encode([
        'success' => true,
        'player_sunk_ships' => $playerSunkShips,    // Los barcos del jugador que han sido hundidos por el oponente
        'opponent_sunk_ships' => $opponentSunkShips // Los barcos del oponente que el jugador ha hundido
    ]);
} catch (PDOException $e) {
    error_log("Error en el servidor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 