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

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_POST['game_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falta el ID del juego']);
    exit;
}

$gameId = (int)$_POST['game_id'];

try {
    // Obtener información del juego
    $stmt = $pdo->prepare("
        SELECT * FROM games
        WHERE id = ? AND status = 'playing' AND is_ai = 1
    ");
    $stmt->execute([$gameId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Juego no encontrado o no es contra la IA']);
        exit;
    }

    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si es el turno de la IA
    if ($game['current_player'] != $game['player2_id']) {
        echo json_encode(['success' => false, 'message' => 'No es el turno de la IA']);
        exit;
    }

    // Obtener disparos anteriores de la IA
    $stmt = $pdo->prepare("
        SELECT x, y, hit FROM shots
        WHERE game_id = ? AND player_id = ?
    ");
    $stmt->execute([$gameId, $game['player2_id']]);
    $previousShots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener barcos del jugador
    $stmt = $pdo->prepare("
        SELECT * FROM ships
        WHERE game_id = ? AND player_id = ?
    ");
    $stmt->execute([$gameId, $game['player1_id']]);
    $playerShips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lógica de la IA para elegir la siguiente posición
    $shot = getAIShot($game['grid_size'], $previousShots, $playerShips);

    // Verificar si el disparo golpea algún barco
    $hit = false;
    $sunk = false;
    $sunkShip = null;

    foreach ($playerShips as $ship) {
        if (isShipHit($shot['x'], $shot['y'], $ship)) {
            $hit = true;
            
            // Verificar si el barco está hundido
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as hits FROM shots
                WHERE game_id = ? AND player_id = ?
                AND EXISTS (
                    SELECT 1 FROM ships s
                    WHERE s.game_id = ? AND s.player_id = ?
                    AND (
                        (s.orientation = 'horizontal' AND shots.y = s.start_y AND shots.x >= s.start_x AND shots.x < s.start_x + s.size)
                        OR
                        (s.orientation = 'vertical' AND shots.x = s.start_x AND shots.y >= s.start_y AND shots.y < s.start_y + s.size)
                    )
                )
            ");
            $stmt->execute([$gameId, $game['player2_id'], $gameId, $game['player1_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['hits'] + 1 >= $ship['size']) {
                $sunk = true;
                $sunkShip = $ship;
            }
            
            break;
        }
    }

    // Registrar el disparo
    $stmt = $pdo->prepare("
        INSERT INTO shots (game_id, player_id, x, y, hit)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$gameId, $game['player2_id'], $shot['x'], $shot['y'], $hit]);

    // Cambiar el turno al jugador
    $stmt = $pdo->prepare("
        UPDATE games
        SET current_player = ?
        WHERE id = ?
    ");
    $stmt->execute([$game['player1_id'], $gameId]);

    echo json_encode([
        'success' => true,
        'x' => $shot['x'],
        'y' => $shot['y'],
        'hit' => $hit,
        'sunk' => $sunk,
        'ship' => $sunkShip
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}

// Función auxiliar para verificar si un disparo golpea un barco
function isShipHit($x, $y, $ship) {
    if ($ship['orientation'] === 'horizontal') {
        return $y === $ship['start_y'] &&
               $x >= $ship['start_x'] &&
               $x < $ship['start_x'] + $ship['size'];
    } else {
        return $x === $ship['start_x'] &&
               $y >= $ship['start_y'] &&
               $y < $ship['start_y'] + $ship['size'];
    }
}

// Función para que la IA elija su siguiente disparo
function getAIShot($gridSize, $previousShots, $playerShips) {
    // Si hay un disparo anterior que golpeó, intentar hundir el barco
    $lastHit = null;
    foreach (array_reverse($previousShots) as $shot) {
        if ($shot['hit']) {
            $lastHit = $shot;
            break;
        }
    }

    if ($lastHit) {
        // Intentar disparar alrededor del último golpe
        $possibleShots = [
            ['x' => $lastHit['x'] + 1, 'y' => $lastHit['y']],
            ['x' => $lastHit['x'] - 1, 'y' => $lastHit['y']],
            ['x' => $lastHit['x'], 'y' => $lastHit['y'] + 1],
            ['x' => $lastHit['x'], 'y' => $lastHit['y'] - 1]
        ];

        foreach ($possibleShots as $shot) {
            if (isValidShot($shot, $gridSize, $previousShots)) {
                return $shot;
            }
        }
    }

    // Si no hay un disparo anterior que golpeó, elegir una posición aleatoria
    do {
        $x = rand(0, $gridSize - 1);
        $y = rand(0, $gridSize - 1);
        $shot = ['x' => $x, 'y' => $y];
    } while (!isValidShot($shot, $gridSize, $previousShots));

    return $shot;
}

// Función para verificar si un disparo es válido
function isValidShot($shot, $gridSize, $previousShots) {
    // Verificar límites
    if ($shot['x'] < 0 || $shot['x'] >= $gridSize || $shot['y'] < 0 || $shot['y'] >= $gridSize) {
        return false;
    }

    // Verificar si ya se disparó en esa posición
    foreach ($previousShots as $previousShot) {
        if ($previousShot['x'] === $shot['x'] && $previousShot['y'] === $shot['y']) {
            return false;
        }
    }

    return true;
}
?> 