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

    // Obtener barcos del jugador
    $stmt = $pdo->prepare("
        SELECT * FROM ships
        WHERE game_id = ? AND player_id = ?
    ");
    $stmt->execute([$gameId, $_SESSION['user_id']]);
    $playerShips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener barcos del oponente
    $opponentId = $game['player1_id'] == $_SESSION['user_id'] ? $game['player2_id'] : $game['player1_id'];
    $stmt = $pdo->prepare("
        SELECT * FROM ships
        WHERE game_id = ? AND player_id = ?
    ");
    $stmt->execute([$gameId, $opponentId]);
    $opponentShips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener disparos recibidos
    $stmt = $pdo->prepare("
        SELECT s.*, u.username as shooter_name
        FROM shots s
        JOIN users u ON s.player_id = u.id
        WHERE s.game_id = ? AND s.player_id != ?
    ");
    $stmt->execute([$gameId, $_SESSION['user_id']]);
    $playerShots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener disparos realizados
    $stmt = $pdo->prepare("
        SELECT s.*, u.username as shooter_name
        FROM shots s
        JOIN users u ON s.player_id = u.id
        WHERE s.game_id = ? AND s.player_id = ?
    ");
    $stmt->execute([$gameId, $_SESSION['user_id']]);
    $opponentShots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar si el juego ha terminado
    $allShipsSunk = true;
    foreach ($opponentShips as $ship) {
        $shipHits = 0;
        foreach ($opponentShots as $shot) {
            if (isShipHit($shot, $ship)) {
                $shipHits++;
            }
        }
        if ($shipHits < $ship['size']) {
            $allShipsSunk = false;
            break;
        }
    }

    if ($allShipsSunk && $game['status'] === 'playing') {
        // Actualizar estado del juego
        $stmt = $pdo->prepare("
            UPDATE games
            SET status = 'finished', winner_id = ?, finished_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $gameId]);
        $game['status'] = 'finished';
        $game['winner_id'] = $_SESSION['user_id'];
    }

    echo json_encode([
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'grid_size' => $game['grid_size'],
            'status' => $game['status'],
            'current_player' => $game['current_player'],
            'winner_id' => $game['winner_id'],
            'player_ships' => $playerShips,
            'opponent_ships' => $opponentShips,
            'player_shots' => $playerShots,
            'opponent_shots' => $opponentShots
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}

// Función auxiliar para verificar si un disparo golpea un barco
function isShipHit($shot, $ship) {
    if ($ship['orientation'] === 'horizontal') {
        return $shot['y'] === $ship['start_y'] &&
               $shot['x'] >= $ship['start_x'] &&
               $shot['x'] < $ship['start_x'] + $ship['size'];
    } else {
        return $shot['x'] === $ship['start_x'] &&
               $shot['y'] >= $ship['start_y'] &&
               $shot['y'] < $ship['start_y'] + $ship['size'];
    }
}
?> 