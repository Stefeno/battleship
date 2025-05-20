<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    // Obtener partidas en configuración que están esperando un segundo jugador
    $stmt = $pdo->prepare("
        SELECT 
            g.id, 
            g.grid_size, 
            u.username as creator,
            u.username as player1_name,
            DATE_FORMAT(g.start_date, '%d/%m/%Y %H:%i') as created_at
        FROM games g
        JOIN users u ON g.player1_id = u.id
        WHERE g.status = 'setup'
        AND g.game_type = 'vs_player'
        AND g.player1_id != ?
        AND g.player2_id IS NULL
        ORDER BY g.start_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'games' => $games
    ]);
} catch (PDOException $e) {
    error_log("Error en el servidor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 