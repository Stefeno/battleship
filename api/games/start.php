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

//$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_POST['game_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de juego no proporcionado']);
    exit;
}

$gameId = (int)$_POST['game_id'];

try {
    // Verificar que el juego existe
    $stmt = $pdo->prepare("
        SELECT * FROM games
        WHERE id = ?
    ");
    $stmt->execute([$gameId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Juego no encontrado']);
        exit;
    }

    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar que el usuario es parte del juego
    if ($game['player1_id'] != $_SESSION['user_id'] && $game['player2_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No eres parte de este juego']);
        exit;
    }

    // Verificar que todos los barcos están colocados
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM ships
        WHERE game_id = ? AND player_id = ?
    ");
    $stmt->execute([$gameId, $_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] < 5) { // 5 es el número total de barcos
        echo json_encode(['success' => false, 'message' => 'Debes colocar todos los barcos antes de empezar']);
        exit;
    }

    // Si es un juego contra la IA, colocar los barcos de la IA
    if ($game['player2_id'] == -1) {
        // Colocar barcos de la IA
        $ships = [
            ['type' => 'portaaviones', 'size' => 5],
            ['type' => 'acorazado', 'size' => 4],
            ['type' => 'crucero', 'size' => 3],
            ['type' => 'submarino', 'size' => 3],
            ['type' => 'destructor', 'size' => 2]
        ];

        foreach ($ships as $ship) {
            $placed = false;
            $attempts = 0;
            $maxAttempts = 100;

            while (!$placed && $attempts < $maxAttempts) {
                $x = rand(0, $game['grid_size'] - 1);
                $y = rand(0, $game['grid_size'] - 1);
                $orientation = rand(0, 1) ? 'horizontal' : 'vertical';

                // Verificar si se puede colocar el barco
                $canPlace = true;
                if ($orientation == 'horizontal') {
                    if ($x + $ship['size'] > $game['grid_size']) {
                        $canPlace = false;
                    } else {
                        for ($i = 0; $i < $ship['size']; $i++) {
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as count FROM ships
                                WHERE game_id = ? AND player_id = -1
                                AND (
                                    (orientation = 'horizontal' AND start_x <= ? AND start_x + size > ? AND start_y = ?)
                                    OR
                                    (orientation = 'vertical' AND start_x = ? AND start_y <= ? AND start_y + size > ?)
                                )
                            ");
                            $stmt->execute([$gameId, $x + $i, $x + $i, $y, $x + $i, $y, $y]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($result['count'] > 0) {
                                $canPlace = false;
                                break;
                            }
                        }
                    }
                } else {
                    if ($y + $ship['size'] > $game['grid_size']) {
                        $canPlace = false;
                    } else {
                        for ($i = 0; $i < $ship['size']; $i++) {
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as count FROM ships
                                WHERE game_id = ? AND player_id = -1
                                AND (
                                    (orientation = 'horizontal' AND start_x <= ? AND start_x + size > ? AND start_y = ?)
                                    OR
                                    (orientation = 'vertical' AND start_x = ? AND start_y <= ? AND start_y + size > ?)
                                )
                            ");
                            $stmt->execute([$gameId, $x, $x, $y + $i, $x, $y + $i, $y + $i]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($result['count'] > 0) {
                                $canPlace = false;
                                break;
                            }
                        }
                    }
                }

                if ($canPlace) {
                    $stmt = $pdo->prepare("
                        INSERT INTO ships (game_id, player_id, ship_type, size, start_x, start_y, orientation)
                        VALUES (?, -1, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$gameId, $ship['type'], $ship['size'], $x, $y, $orientation]);
                    $placed = true;
                }
                $attempts++;
            }
        }

        // Actualizar estado del juego
        $stmt = $pdo->prepare("
            UPDATE games
            SET status = 'playing',
                current_player = player1_id
            WHERE id = ?
        ");
        $stmt->execute([$gameId]);
    } else {
        // Si es contra otro jugador, verificar si ambos han colocado sus barcos
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM ships WHERE game_id = ? AND player_id = ?) as player1_ships,
                (SELECT COUNT(*) FROM ships WHERE game_id = ? AND player_id = ?) as player2_ships
        ");
        $stmt->execute([$gameId, $game['player1_id'], $gameId, $game['player2_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['player1_ships'] < 5 || $result['player2_ships'] < 5) {
            echo json_encode(['success' => false, 'message' => 'Ambos jugadores deben colocar todos sus barcos antes de empezar']);
            exit;
        }

        // Actualizar estado del juego
        $stmt = $pdo->prepare("
            UPDATE games
            SET status = 'playing',
                current_player = player1_id,
                start_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$gameId]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Partida iniciada exitosamente'
    ]);
} catch (PDOException $e) {
    error_log("Error en el servidor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 