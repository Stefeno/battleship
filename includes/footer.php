    </div> <!-- Cierre del contenedor principal -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Cargar traducciones en JavaScript
        const translations = <?= json_encode(Translations::getAll()) ?>;
        
        // Función helper para traducciones en JavaScript
        function t(key) {
            return translations[key] || key;
        }
        
        // Manejar cambio de idioma
        $(document).ready(function() {
            const languageSelect = $('#languageSelect');
            
            languageSelect.change(function() {
                const lang = $(this).val();
                console.log('Cambiando idioma a:', lang);
                
                $.ajax({
                    url: 'api/language/change.php',
                    method: 'POST',
                    data: { language: lang },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Respuesta del servidor:', response);
                        if (response && response.success) {
                            // Recargar la página para aplicar los cambios
                            window.location.reload();
                        } else {
                            alert(response.message || 'Error al cambiar el idioma');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en la petición:', error);
                        console.error('Estado:', status);
                        console.error('Respuesta:', xhr.responseText);
                        alert('Error al cambiar el idioma: ' + error);
                    }
                });
            });

            // Login form
            $('#loginForm').submit(function(e) {
                e.preventDefault();
                const username = $('#username').val();
                const password = $('#password').val();
                
                $.ajax({
                    url: 'api/auth/login.php',
                    method: 'POST',
                    data: { username, password },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = 'game.php';
                        } else {
                            alert(t('error_login'));
                        }
                    }
                });
            });
        });
    </script>
</body>
</html> 