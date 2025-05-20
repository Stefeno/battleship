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

if (!isset($_POST['game_id']) || !isset($_POST['ship_type']) || !isset($_POST['ship_size']) || 
    !isset($_POST['start_x']) || !isset($_POST['start_y']) || !isset($_POST['orientation'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

$gameId = (int)$_POST['game_id'];
$shipType = $_POST['ship_type'];
$shipSize = (int)$_POST['ship_size'];
$startX = (int)$_POST['start_x'];
$startY = (int)$_POST['start_y'];
$orientation = $_POST['orientation'];

try {
    // Verificar si el juego existe y está en estado de configuración
    $stmt = $pdo->prepare("
        SELECT * FROM games
        WHERE id = ? AND status = 'setup'
    ");
    $stmt->execute([$gameId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Juego no encontrado o no está en configuración']);
        exit;
    }

    // Verificar si el usuario es parte del juego
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($game['player1_id'] != $_SESSION['user_id'] && $game['player2_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No eres parte de este juego']);
        exit;
    }

    // Verificar si el barco ya fue colocado
    $stmt = $pdo->prepare("
        SELECT * FROM ships
        WHERE game_id = ? AND player_id = ? AND ship_type = ?
    ");
    $stmt->execute([$gameId, $_SESSION['user_id'], $shipType]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Este barco ya fue colocado']);
        exit;
    }

    // Verificar límites del tablero
    $endX = $orientation === 'horizontal' ? $startX + $shipSize - 1 : $startX;
    $endY = $orientation === 'vertical' ? $startY + $shipSize - 1 : $startY;
    
    if ($endX >= $game['grid_size'] || $endY >= $game['grid_size']) {
        echo json_encode(['success' => false, 'message' => 'El barco excede los límites del tablero']);
        exit;
    }

    // Verificar colisiones con otros barcos
    $stmt = $pdo->prepare("
        SELECT * FROM ships
        WHERE game_id = ? AND player_id = ?
    ");
    $stmt->execute([$gameId, $_SESSION['user_id']]);
    $existingShips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($existingShips as $ship) {
        for ($i = 0; $i < $shipSize; $i++) {
            $newX = $orientation === 'horizontal' ? $startX + $i : $startX;
            $newY = $orientation === 'vertical' ? $startY + $i : $startY;
            
            for ($j = 0; $j < $ship['size']; $j++) {
                $existingX = $ship['orientation'] === 'horizontal' ? $ship['start_x'] + $j : $ship['start_x'];
                $existingY = $ship['orientation'] === 'vertical' ? $ship['start_y'] + $j : $ship['start_y'];
                
                if ($newX === $existingX && $newY === $existingY) {
                    echo json_encode(['success' => false, 'message' => 'El barco colisiona con otro barco']);
                    exit;
                }
            }
        }
    }

    // Insertar el barco
    $stmt = $pdo->prepare("
        INSERT INTO ships (game_id, player_id, ship_type, size, start_x, start_y, orientation)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $gameId,
        $_SESSION['user_id'],
        $shipType,
        $shipSize,
        $startX,
        $startY,
        $orientation
    ]);

    echo json_encode(['success' => true, 'message' => 'Barco colocado exitosamente']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 