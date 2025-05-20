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
    <title><?= t('battle_ship') ?> (by Stefano Gaviglia)</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="waves">
        <div class="wave wave1"></div>
        <div class="wave wave2"></div>
        <div class="wave wave3"></div>
    </div>
    <div class="container">
        <div class="game-title">
            <i class="fas fa-ship"></i>
            <h1 class="animate__animated animate__bounce"><?= t('battle_ship') ?> </h1>
            <span style="font-size: x-small;" class="animate__animated animate__bounce">(v. <?= VERSION ?> <br>by Stefano <br>Gaviglia)</span>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div id="loginForm">
                            <form id="login" method="post">
                                <div class="mb-3 input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" placeholder="<?= t('username') ?>" required>
                                </div>
                                <div class="mb-3 input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" placeholder="<?= t('password') ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i><?= t('login') ?>
                                </button>
                            </form>
                            <div class="text-center mt-3">
                                <button id="showRegister" class="btn btn-link">
                                    <i class="fas fa-user-plus me-2"></i><?= t('new_user') ?>
                                </button>
                            </div>
                        </div>
                        <div id="registerForm" style="display: none;">
                            <form id="register" method="post">
                                <div class="mb-3 input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="newUsername" placeholder="<?= t('username') ?>" required>
                                </div>
                                <div class="mb-3 input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="newPassword" placeholder="<?= t('password') ?>" required>
                                </div>
                                <div class="mb-3 input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirmPassword" placeholder="<?= t('confirm_password') ?>" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-user-plus me-2"></i><?= t('register') ?>
                                </button>
                            </form>
                            <div class="text-center mt-3">
                                <button id="showLogin" class="btn btn-link">
                                    <i class="fas fa-sign-in-alt me-2"></i><?= t('is_user') ?>
                                </button>
                            </div>
                        </div>
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
    <script src="assets/js/main.js?v=<?= VERSION ?>"></script>
</body>
</html> 