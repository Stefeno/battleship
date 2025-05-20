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

if (!isset($_POST['grid_size']) || !isset($_POST['game_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

$gridSize = (int)$_POST['grid_size'];
$gameType = $_POST['game_type'];



// Validar tamaño del tablero
if (!in_array($gridSize, [8, 10, 12])) {
    echo json_encode(['success' => false, 'message' => 'Tamaño de tablero no válido']);
    exit;
}

// Validar tipo de juego
if (!in_array($gameType, ['vs_ai', 'vs_player'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de juego no válido']);
    exit;
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // Crear nueva partida
    $stmt = $pdo->prepare("
        INSERT INTO games (player1_id, grid_size, status, game_type)
        VALUES (?, ?, 'setup', ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $gridSize, $gameType]);
    $gameId = $pdo->lastInsertId();

    // Si es contra la IA, establecer player2_id como -1
    if ($gameType === 'vs_ai') {
        $stmt = $pdo->prepare("
            UPDATE games
            SET player2_id = -1
            WHERE id = ?
        ");
        $stmt->execute([$gameId]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $gameType === 'vs_ai' ? 'Partida creada exitosamente' : 'Partida creada. Esperando a que otro jugador se una.',
        'game_id' => $gameId,
        'game_type' => $gameType
    ]);
} catch (PDOException $e) {
    error_log("Error en el servidor" . $e);
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 