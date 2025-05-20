<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['game_id']) || !isset($_POST['x']) || !isset($_POST['y'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$gameId = (int)$_POST['game_id'];
$x = (int)$_POST['x'];
$y = (int)$_POST['y'];
$isAI = isset($_POST['is_ai']) && $_POST['is_ai'] === 'true';

try {
    // Obtener información del juego
    $stmt = $pdo->prepare("
        SELECT g.*, 
               p1.username as player1_username,
               p2.username as player2_username
        FROM games g
        LEFT JOIN users p1 ON g.player1_id = p1.id
        LEFT JOIN users p2 ON g.player2_id = p2.id
        WHERE g.id = ?
    ");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        echo json_encode(['success' => false, 'message' => 'Partida no encontrada']);
        exit;
    }

    if ($game['status'] !== 'playing') {
        echo json_encode(['success' => false, 'message' => 'La partida no está en curso']);
        exit;
    }

    // Determinar el ID del jugador que dispara
    $shooterId = $isAI ? -1 : $_SESSION['user_id'];

    // Verificar si es el turno del jugador o de la IA
    if ($game['current_player'] != $shooterId) {
        echo json_encode(['success' => false, 'message' => 'No es tu turno']);
        exit;
    }

    // Verificar que las coordenadas estén dentro del tablero
    if ($x < 0 || $x >= $game['grid_size'] || $y < 0 || $y >= $game['grid_size']) {
        echo json_encode(['success' => false, 'message' => 'Coordenadas inválidas']);
        exit;
    }

    // Verificar si ya se ha disparado en esa posición
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM shots 
        WHERE game_id = ? 
        AND x = ? 
        AND y = ? 
        AND player_id = ?
    ");
    $stmt->execute([$gameId, $x, $y, $shooterId]);
    $shotExists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    if ($shotExists) {
        echo json_encode(['success' => false, 'message' => 'Ya has disparado en esa posición']);
        exit;
    }

    // Obtener los barcos del oponente
    $opponentId = $game['player1_id'] == $shooterId ? $game['player2_id'] : $game['player1_id'];
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM ships s
        WHERE s.game_id = ? AND s.player_id = ?
    ");
    $stmt->execute([$gameId, $opponentId]);
    $ships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar si el disparo impacta en algún barco
    $hit = false;
    $sunk = false;
    $sunkShip = null;

    foreach ($ships as $ship) {
        if ($ship['orientation'] === 'horizontal') {
            if ($y == $ship['start_y'] && $x >= $ship['start_x'] && $x < $ship['start_x'] + $ship['size']) {
                $hit = true;
                // Verificar si el barco está hundido
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as hits
                    FROM shots
                    WHERE game_id = ? AND player_id = ?
                    AND x >= ? AND x < ? AND y = ?
                ");
                $stmt->execute([
                    $gameId,
                    $shooterId,
                    $ship['start_x'],
                    $ship['start_x'] + $ship['size'],
                    $ship['start_y']
                ]);
                $hits = $stmt->fetch(PDO::FETCH_ASSOC)['hits'];
                if ($hits + 1 == $ship['size']) {
                    $sunk = true;
                    $sunkShip = $ship;
                }
                break;
            }
        } else {
            if ($x == $ship['start_x'] && $y >= $ship['start_y'] && $y < $ship['start_y'] + $ship['size']) {
                $hit = true;
                // Verificar si el barco está hundido
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as hits
                    FROM shots
                    WHERE game_id = ? AND player_id = ?
                    AND x = ? AND y >= ? AND y < ?
                ");
                $stmt->execute([
                    $gameId,
                    $shooterId,
                    $ship['start_x'],
                    $ship['start_y'],
                    $ship['start_y'] + $ship['size']
                ]);
                $hits = $stmt->fetch(PDO::FETCH_ASSOC)['hits'];
                if ($hits + 1 == $ship['size']) {
                    $sunk = true;
                    $sunkShip = $ship;
                }
                break;
            }
        }
    }

    // Insertar el disparo
    $stmt = $pdo->prepare("
        INSERT INTO shots (game_id, player_id, x, y, hit)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$gameId, $shooterId, $x, $y, $hit]);

    // Verificar si el juego ha terminado
    $gameFinished = false;
    $winner = null;

    if ($sunk) {
        // Verificar si todos los barcos del oponente están hundidos
        $stmt = $pdo->prepare("
            WITH sunk_ships AS (
                SELECT s.id, s.size,
                       COUNT(sh.id) as hits
                FROM ships s
                LEFT JOIN shots sh ON sh.game_id = s.game_id 
                    AND sh.player_id = ?
                    AND (
                        (s.orientation = 'horizontal' AND sh.x >= s.start_x AND sh.x < s.start_x + s.size AND sh.y = s.start_y)
                        OR
                        (s.orientation = 'vertical' AND sh.x = s.start_x AND sh.y >= s.start_y AND sh.y < s.start_y + s.size)
                    )
                WHERE s.game_id = ? AND s.player_id = ?
                GROUP BY s.id, s.size
            )
            SELECT 
                COUNT(*) as total_ships,
                SUM(CASE WHEN hits = size THEN 1 ELSE 0 END) as sunk_ships
            FROM sunk_ships
        ");
        $stmt->execute([$shooterId, $gameId, $opponentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['total_ships'] > 0 && $result['total_ships'] == $result['sunk_ships']) {
            $gameFinished = true;
            $winner = $shooterId;
        }
    }

    // Verificar si hay celdas disponibles para disparar
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(DISTINCT CONCAT(x, ',', y)) FROM shots WHERE game_id = ?) as shot_cells,
            ? * ? as total_cells
    ");
    $stmt->execute([$gameId, $game['grid_size'], $game['grid_size']]);
    $cellsResult = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cellsResult['total_cells'] == $cellsResult['shot_cells']) {
        $gameFinished = true;
        // Si no hay ganador, es un empate
        if (!$winner) {
            $winner = -2; // -2 representa empate
        }
    }

    // Actualizar el estado del juego
    if ($gameFinished) {
        $stmt = $pdo->prepare("
            UPDATE games
            SET status = 'finished',
                winner_id = ?,
                current_player = NULL,
                finished_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$winner, $gameId]);
    } else {
        // Cambiar el turno solo si hay celdas disponibles
        if ($cellsResult['total_cells'] > $cellsResult['shot_cells']) {
            $nextPlayerId = $game['player1_id'] == $shooterId ? $game['player2_id'] : $game['player1_id'];
            $stmt = $pdo->prepare("
                UPDATE games
                SET current_player = ?
                WHERE id = ?
            ");
            $stmt->execute([$nextPlayerId, $gameId]);
        }
    }

    // Devolver la respuesta
    echo json_encode([
        'success' => true,
        'hit' => $hit,
        'sunk' => $sunk,
        'ship' => $sunkShip,
        'game_status' => $gameFinished ? 'finished' : 'playing',
        'winner_id' => $winner
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?> 