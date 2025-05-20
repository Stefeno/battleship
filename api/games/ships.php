<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($_GET['game_id']) || !isset($_GET['player_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$gameId = (int)$_GET['game_id'];
$playerId = (int)$_GET['player_id'];

require_once '../../config/database.php';

try {
    // Obtener los barcos del jugador
    $stmt = $pdo->prepare("
        SELECT s.id, s.size, s.start_x, s.start_y, s.orientation
        FROM ships s
        WHERE s.game_id = ? AND s.player_id = ?
    ");
    $stmt->execute([$gameId, $playerId]);
    $ships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'ships' => $ships
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los barcos: ' . $e->getMessage()
    ]);
} 