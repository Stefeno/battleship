$(document).ready(function() {
    // Manejo del cambio entre formularios de login y registro
    $('#showRegister').click(function() {
        $('#loginForm').hide();
        $('#registerForm').show();
    });

    $('#showLogin').click(function() {
        $('#registerForm').hide();
        $('#loginForm').show();
    });

    // Manejo del formulario de login
    $('#login').submit(function(e) {
        e.preventDefault();
        const username = $('#username').val();
        const password = $('#password').val();
        // alert('hola ' + username + ' ' + password); // debug
        $.ajax({
            url: 'api/auth/login.php',
            method: 'POST',
            data: {
                username: username,
                password: password
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = 'game.php';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error al conectar con el servidor');
            }
        });
    });

    // Manejo del formulario de registro
    $('#register').submit(function(e) {
        e.preventDefault();
        const username = $('#newUsername').val();
        const password = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();

        if (password !== confirmPassword) {
            alert('Las contraseñas no coinciden');
            return;
        }

        $.ajax({
            url: 'api/auth/register.php',
            method: 'POST',
            data: {
                username: username,
                password: password
            },
            success: function(response) {
                if (response.success) {
                    alert('Registro exitoso. Por favor, inicia sesión.');
                    $('#registerForm').hide();
                    $('#loginForm').show();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error al conectar con el servidor');
            }
        });
    });
}); 