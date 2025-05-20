$(document).ready(function() {
    // Cargar partidas disponibles y records
    loadGames();
    loadRecords();

    // Manejar cierre de sesión
    $('#logout').click(function() {
        $.ajax({
            url: 'api/auth/logout.php',
            method: 'POST',
            success: function() {
                window.location.href = 'index.php';
            }
        });
    });

    // Manejar creación de nueva partida
    $('#newGameForm').submit(function(e) {
        e.preventDefault();
        const gridSize = $('#gridSize').val();
        const gameType = $('#gameType').val();

        $.ajax({
            url: 'api/games/create.php',
            method: 'POST',
            data: {
                grid_size: gridSize,
                game_type: gameType
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = 'setup.php?game_id=' + response.game_id;
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    });

    // Función para cargar partidas disponibles
    function loadGames() {
        const gamesList = $('#gamesList');
        gamesList.html('<div class="list-group-item bg-dark text-light border-secondary"><div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Cargando partidas...</div></div>');

        $.ajax({
            url: 'api/games/list.php',
            method: 'GET',
            success: function(response) {
                gamesList.empty();

                if (!response.success) {
                    gamesList.append('<div class="list-group-item bg-dark text-light border-secondary">Error al cargar las partidas</div>');
                    return;
                }

                if (response.games.length === 0) {
                    gamesList.append('<div class="list-group-item bg-dark text-light border-secondary">No hay partidas disponibles</div>');
                    return;
                }

                response.games.forEach(function(game) {
                    const gameItem = $('<div class="list-group-item bg-dark text-light border-secondary">');
                    gameItem.append(`
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 text-primary">Partida de ${game.creator}</h5>
                                <div class="text-light-50">
                                    <span class="me-3">
                                        <i class="fas fa-chess-board me-1"></i>
                                        Tablero: ${game.grid_size}x${game.grid_size}
                                    </span>
                                    <span>
                                        <i class="far fa-clock me-1"></i>
                                        ${game.created_at}
                                    </span>
                                </div>
                            </div>
                            <button class="btn btn-outline-primary join-game" data-game-id="${game.id}">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                Unirse
                            </button>
                        </div>
                    `);
                    gamesList.append(gameItem);
                });

                // Manejar clic en botón de unirse
                $('.join-game').click(function() {
                    const gameId = $(this).data('game-id');
                    const button = $(this);
                    
                    // Deshabilitar el botón mientras se procesa
                    button.prop('disabled', true);
                    button.html('<i class="fas fa-spinner fa-spin me-1"></i> Uniendo...');

                    $.ajax({
                        url: 'api/games/join.php',
                        method: 'POST',
                        data: {
                            game_id: gameId
                        },
                        success: function(response) {
                            if (response.success) {
                                window.location.href = 'setup.php?game_id=' + gameId;
                            } else {
                                alert('Error: ' + response.message);
                                // Restaurar el botón
                                button.prop('disabled', false);
                                button.html('<i class="fas fa-sign-in-alt me-1"></i> Unirse');
                            }
                        },
                        error: function() {
                            alert('Error al unirse al juego');
                            // Restaurar el botón
                            button.prop('disabled', false);
                            button.html('<i class="fas fa-sign-in-alt me-1"></i> Unirse');
                        }
                    });
                });
            },
            error: function() {
                gamesList.empty();
                gamesList.append('<div class="list-group-item bg-dark text-light border-secondary">Error al cargar las partidas</div>');
            }
        });
    }

    // Función para cargar los records
    function loadRecords() {
        const recordsList = $('#recordsList');
        recordsList.html('<div class="list-group-item bg-dark text-light border-secondary"><div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Cargando records...</div></div>');

        $.ajax({
            url: 'api/games/records.php',
            method: 'GET',
            success: function(response) {
                recordsList.empty();

                if (!response.success) {
                    recordsList.append('<div class="list-group-item bg-dark text-light border-secondary">Error al cargar los records</div>');
                    return;
                }

                if (response.records.length === 0) {
                    recordsList.append('<div class="list-group-item bg-dark text-light border-secondary">No hay records disponibles</div>');
                    return;
                }

                response.records.forEach(function(record) {
                    console.log('winnerid: ' + record.winner_id + ' player1_id:' + record.player1_id);
                    const winner = record.winner_id == record.player1_id ? record.player1_name : record.player2_name;
                    const loser = record.winner_id == record.player1_id ? record.player2_name : record.player1_name;
                    
                    const recordItem = $('<div class="list-group-item bg-dark text-light border-secondary">');
                    recordItem.append(`
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 text-primary">${winner} vs ${loser}</h5>
                                <div class="text-light-50">
                                    <span class="me-3">
                                        <i class="fas fa-trophy me-1"></i>
                                        Ganador: ${winner}
                                    </span>
                                    <span class="me-3">
                                        <i class="fas fa-chess-board me-1"></i>
                                        Tablero: ${record.grid_size}x${record.grid_size}
                                    </span>
                                    <span>
                                        <i class="far fa-clock me-1"></i>
                                        Duración: ${record.duration_minutes} minutos
                                    </span>
                                </div>
                            </div>
                        </div>
                    `);
                    recordsList.append(recordItem);
                });
            },
            error: function() {
                recordsList.empty();
                recordsList.append('<div class="list-group-item bg-dark text-light border-secondary">Error al cargar los records</div>');
            }
        });
    }

    // Actualizar lista de partidas y records cada 30 segundos
    setInterval(function() {
        loadGames();
        loadRecords();
    }, 30000);
}); 