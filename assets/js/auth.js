$(document).ready(function() {
    // Manejar el envío del formulario de registro
    $('#register').submit(function(e) {
        e.preventDefault();
        
        const username = $('#newUsername').val().trim();
        const password = $('#newPassword').val().trim();
        const confirmPassword = $('#confirmPassword').val().trim();
        
        // Validaciones básicas
        if (!username || !password || !confirmPassword) {
            alert('Por favor, complete todos los campos');
            return;
        }

        if (password !== confirmPassword) {
            alert('Las contraseñas no coinciden');
            return;
        }
        
        // Enviar datos al servidor
        $.ajax({
            url: 'api/auth/register.php',
            method: 'POST',
            data: {
                username: username,
                password: password
            },
            success: function(response) {
                if (response.success) {
                    alert('Registro exitoso');
                    window.location.href = 'index.php';
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    alert(response.message);
                } catch (e) {
                    alert('Error en el servidor');
                }
            }
        });
    });

    // Manejar el envío del formulario de login
    $('#login').submit(function(e) {
        e.preventDefault();
        
        const username = $('#username').val().trim();
        const password = $('#password').val().trim();
        
        // Validaciones básicas
        if (!username || !password) {
            alert('Por favor, complete todos los campos');
            return;
        }
        
        // Enviar datos al servidor
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
                    alert(response.message);
                }
            },
            error: function(xhr) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    alert(response.message);
                } catch (e) {
                    alert('Error en el servidor');
                }
            }
        });
    });

    // Manejar el cambio entre formularios de login y registro
    $('#showRegister').click(function() {
        $('#loginForm').hide();
        $('#registerForm').show();
    });

    $('#showLogin').click(function() {
        $('#registerForm').hide();
        $('#loginForm').show();
    });
}); 