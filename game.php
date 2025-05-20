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
                    <div>
                        <span class="me-3"><?= t('welcome_usr') ?>, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <button id="logout" class="btn btn-danger"><?= t('logout') ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card bg-dark text-light">
                    <div class="card-header">
                        <h4><?= t('online_games') ?></h4>
                    </div>
                    <div class="card-body">
                        <div id="gamesList" class="list-group">
                            <!-- Las partidas disponibles se cargarán aquí -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-dark text-light">
                    <div class="card-header">
                        <h4><?= t('last_games') ?></h4>
                    </div>
                    <div class="card-body">
                        <div id="recordsList" class="list-group">
                            <!-- Los records se cargarán aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-dark border-light">
                    <div class="card-header">
                        <h3><?= t('new_games') ?></h3>
                    </div>
                    <div class="card-body">
                        <form id="newGameForm">
                            <div class="mb-3">
                                <label for="gridSize" class="form-label"><?= t('table_measures') ?></label>
                                <select class="form-select" id="gridSize" required>
                                    <option value="8">8x8</option>
                                    <option value="10" selected>10x10</option>
                                    <option value="12">12x12</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="gameType" class="form-label"><?= t('game_type') ?></label>
                                <select class="form-select" id="gameType" required>
                                    <option value="vs_ai"><?= t('against_ai') ?></option>
                                    <option value="vs_player"><?= t('against_player') ?></option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?= t('create_match') ?></button>
                        </form>
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
    <script src="assets/js/game.js?v=<?= VERSION ?>"></script>
</body>
</html> 