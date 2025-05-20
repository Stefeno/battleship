<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($_GET['game_id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$gameId = (int)$_GET['game_id'];

require_once '../../config/database.php';

try {
    // Obtener todos los disparos del juego
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM shots s
        WHERE s.game_id = ?
        ORDER BY s.created_at ASC
    ");
    $stmt->execute([$gameId]);
    $shots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'shots' => $shots
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los disparos: ' . $e->getMessage()
    ]);
} 