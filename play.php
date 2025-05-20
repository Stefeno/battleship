<?php
include 'config/version.php';
require_once './config/languages.php';
require_once './includes/translations.php';
// Inicializar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener el idioma actual
$currentLanguage = getCurrentLanguage();

// Cargar las traducciones
Translations::init();
//session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['game_id'])) {
    header('Location: game.php');
    exit;
}

$gameId = (int)$_GET['game_id'];
?>
<!DOCTYPE html>
<html lang="<?= $currentLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<!--    icons files -->
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Battle Ship" />
    <link rel="manifest" href="/site.webmanifest" />
<!--  end  icons files -->
    <title><?= t('battle_ship') ?> - <?= t('game') ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-dark text-light">
    <div class="container">
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1><?= t('battle_ship') ?></h1>
                    <div class="d-flex align-items-center">
                        <div id="turnIndicator" class="alert alert-info me-3" style="display: none;">
                            <i class="fas fa-clock"></i> <span id="turnText"><?= t('your_turn') ?></span>
                        </div>
                        <button id="pauseGame" class="btn btn-warning me-3">
                            <i class="fas fa-pause"></i> <?= t('pause') ?>
                        </button>
                        <button id="backToGames" class="btn btn-secondary"><?= t('goback_games') ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card bg-dark border-light">
                    <div class="card-header">
                        <h3><?= t('opponent_board') ?></h3>
                    </div>
                    <div class="card-body">
                        <div id="opponentGrid" class="grid">
                            <!-- La cuadrícula se generará dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark border-light">
                    <div class="card-header">
                        <h3>Chat</h3>
                    </div>
                    <div class="card-body">
                        <div id="messages" class="messages">
                            <!-- Los mensajes se cargarán aquí -->
                        </div>
                        <form id="messageForm" class="mt-3">
                            <div class="input-group">
                                <input type="text" id="messageInput" class="form-control" placeholder="<?= t('type_message') ?>">
                            </div>
                                <button type="submit" class="btn btn-primary"><?= t('send_message') ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card bg-dark border-light">
                    <div class="card-header">
                        <h3><?= t('your_board') ?></h3>
                    </div>
                    <div class="card-body">
                        <div id="playerGrid" class="grid">
                            <!-- La cuadrícula se generará dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="game-container">
        <div class="score-container">
            <div id="playerScore" class="score"><?= t('your_sunked_ships') ?> 0</div>
            <div id="opponentScore" class="score"><?= t('opponent_sunked_ships') ?> 0</div>
            <!--<button id="debugEndGame" class="btn btn-danger btn-sm">Debug: Terminar Juego</button>-->
        </div>
        <div id="gameMessage" class="game-message"></div>
<!--        <div class="grid-container">
            <div class="grid">
                <h3>Tu Tablero</h3>
                <div id="playerGrid" class="battleship-grid"></div>
            </div>
            <div class="grid">
                <h3>Tablero del Oponente</h3>
                <div id="opponentGrid" class="battleship-grid"></div>
            </div>
        </div>-->
    </div>

    <!-- Audio para efectos de sonido -->
    <audio id="hitSound" src="assets/sounds/hit.mp3" preload="auto"></audio>
    <audio id="missSound" src="assets/sounds/miss.mp3" preload="auto"></audio>
    <audio id="sunkSound" src="assets/sounds/sunk.mp3" preload="auto"></audio>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script>
        const USER_ID = <?php echo $_SESSION['user_id']; ?>;
        const THIS_GAME_ID = <?php echo $gameId; ?>;
    </script>
    <script src="assets/js/play.js?v=<?= VERSION ?>"></script>
</body>
</html> 