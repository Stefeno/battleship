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

// Si no hay game_id, redirigir a game.php para crear uno
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
    <title><?= t('battle_ship') ?> - <?= t('setup') ?></title>
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
                    <h1><?= t('setup_game') ?></h1>
                    <button id="backToGames" class="btn btn-secondary"><?= t('goback_games') ?></button>
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
                        <div id="gameGrid" class="grid">
                            <!-- La cuadrícula se generará dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Naves Disponibles</h4>
                    </div>
                    <div class="card-body">
                        <div class="ship-selector" id="portaaviones">
                            <span><?= t('carrier') ?> (5)</span>
                            <span class="badge bg-primary" id="portaavionesCount">1</span>
                        </div>
                        <div class="ship-selector" id="acorazado">
                            <span><?= t('battleship') ?> (4)</span>
                            <span class="badge bg-primary" id="acorazadoCount">1</span>
                        </div>
                        <div class="ship-selector" id="crucero">
                            <span><?= t('cruiser') ?>  (3)</span>
                            <span class="badge bg-primary" id="cruceroCount">1</span>
                        </div>
                        <div class="ship-selector" id="submarino">
                            <span><?= t('submarine') ?> (3)</span>
                            <span class="badge bg-primary" id="submarinoCount">1</span>
                        </div>
                        <div class="ship-selector" id="destructor">
                            <span><?= t('destroy') ?> (2)</span>
                            <span class="badge bg-primary" id="destructorCount">1</span>
                        </div>
                        <button id="rotateShip" class="btn btn-secondary mt-3">↔ <?= t('rotate_ship') ?></button>
                        <button id="startGame" class="btn btn-success mt-3" style="display: none;"><?= t('start_game') ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/setup.js?v=<?= VERSION ?>"></script>
</body>
</html> 