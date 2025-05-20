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
    // Obtener las últimas 10 partidas finalizadas
    //         AND g.game_type = 'vs_player'

    $stmt = $pdo->prepare("
        SELECT 
            g.id,
            g.grid_size,
            g.winner_id,
            g.start_date,
            g.finished_at,
            u1.username as player1_name,
            u1.id as player1_id,
            u2.username as player2_name,
            u2.id as player2_id,
            TIMESTAMPDIFF(MINUTE, g.start_date, g.finished_at) as duration_minutes
        FROM games g
        JOIN users u1 ON g.player1_id = u1.id
        LEFT JOIN users u2 ON g.player2_id = u2.id
        WHERE g.status = 'finished'
        ORDER BY g.finished_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'records' => $records
    ]);
} catch (PDOException $e) {
    error_log("Error en el servidor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?> 