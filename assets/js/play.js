$(document).ready(function() {
//    const gameId = new URLSearchParams(window.location.search).get('game_id');
    const gameId = THIS_GAME_ID;
    
    // Obtener el ID del usuario 
    const userId = USER_ID;
    if (isNaN(userId)) {
        console.error('No se pudo obtener el ID del usuario');
        return;
    }
    
    let gridSize = 10;
    let isPlayerTurn = false;
    let gameStatus = 'playing';
    let isAgainstAI = false;
    let isPaused = false;
    let checkTurnInterval;

    // Actualizar el indicador de turno
    function updateTurnIndicator() {
        const turnIndicator = $('#turnIndicator');
        const turnText = $('#turnText');
        const pauseButton = $('#pauseGame');
        
        if (gameStatus !== 'playing') {
            turnIndicator.hide();
            pauseButton.hide();
            return;
        }

        turnIndicator.show();
        pauseButton.show();
        
        if (isPlayerTurn) {
            turnIndicator.removeClass('alert-warning').addClass('alert-info');
            turnText.text('Es tu turno');
            pauseButton.prop('disabled', false);
        } else {
            turnIndicator.removeClass('alert-info').addClass('alert-warning');
            turnText.text(isAgainstAI ? 'Turno de la IA' : 'Esperando al oponente...');
            pauseButton.prop('disabled', true);
        }
    }

    // Verificar el turno actual
    function checkTurn() {
        $.ajax({
            url: 'api/games/info.php',
            method: 'GET',
            data: { game_id: gameId },
            success: function(response) {
                if (response.success) {
                    const newTurn = response.game.current_player == userId;
                    if (newTurn !== isPlayerTurn) {
                        isPlayerTurn = newTurn;
                        updateTurnIndicator();
                        
                        if (isPlayerTurn) {
                            // Notificar al jugador que es su turno
                            if (Notification.permission === "granted") {
                                new Notification("Batalla Naval", {
                                    body: "Es tu turno de disparar",
                                    icon: "assets/images/icon.png"
                                });
                            }
                        } else if (isAgainstAI) {
                            // Solo si es contra la IA y es su turno, hacer que dispare
                            setTimeout(makeAITurn, 1500);
                        }
                    }
                }
            }
        });
    }

    // Cargar información del juego
    function loadGameInfo() {
        $.ajax({
            url: 'api/games/info.php',
            method: 'GET',
            data: { game_id: gameId },
            success: function(response) {
                if (response.success) {
                    gridSize = response.game.grid_size;
                    isPlayerTurn = response.game.current_player == userId;
                    gameStatus = response.game.status;
                    isAgainstAI = response.game.player2_id === -1;
                    
                    // Inicializar cuadrículas
                    initializeGrids();
                    
                    // Cargar barcos del jugador
                    loadPlayerShips();
                    
                    // Cargar disparos
                    loadShots();
                    
                    // Actualizar indicador de turno
                    updateTurnIndicator();
                    
                    // Actualizar puntuación
                    updateScore();
                    
                    // Iniciar verificación periódica de turnos
                    if (!checkTurnInterval) {
                        checkTurnInterval = setInterval(checkTurn, 2000);
                    }
                    
                    // Solo si es contra la IA y es su turno inicial
                    if (!isPlayerTurn && isAgainstAI && gameStatus === 'playing') {
                        setTimeout(makeAITurn, 1500);
                    }
                }
            }
        });
    }

    // Cargar disparos
    function loadShots() {
        $.ajax({
            url: 'api/games/shots.php',
            method: 'GET',
            data: { game_id: gameId },
            success: function(response) {
                if (response.success) {
                    // Limpiar las clases de hit y miss de ambas cuadrículas
                    $('.cell').removeClass('hit miss sunk');
                    
                    response.shots.forEach(function(shot) {
                        // Los disparos del jugador se muestran en la cuadrícula del oponente
                        if (shot.player_id == userId) {
                            const cell = $(`#opponentGrid .cell[data-x="${shot.x}"][data-y="${shot.y}"]`);
                            if (shot.hit) {
                                cell.addClass('hit');
                            } else {
                                cell.addClass('miss');
                            }
                        }
                        // Los disparos de la IA se muestran en la cuadrícula del jugador
                        else {
                            const cell = $(`#playerGrid .cell[data-x="${shot.x}"][data-y="${shot.y}"]`);
                            if (shot.hit) {
                                cell.addClass('hit');
                            } else {
                                cell.addClass('miss');
                            }
                        }
                    });
                }
            }
        });
    }

    // Inicializar cuadrículas
    function initializeGrids() {
        const playerGrid = $('#playerGrid');
        const opponentGrid = $('#opponentGrid');

        playerGrid.css('grid-template-columns', `repeat(${gridSize}, 1fr)`);
        opponentGrid.css('grid-template-columns', `repeat(${gridSize}, 1fr)`);

        playerGrid.empty();
        opponentGrid.empty();

        for (let y = 0; y < gridSize; y++) {
            for (let x = 0; x < gridSize; x++) {
                const playerCell = $('<div class="cell">');
                playerCell.attr('data-x', x);
                playerCell.attr('data-y', y);
                playerGrid.append(playerCell);

                const opponentCell = $('<div class="cell">');
                opponentCell.attr('data-x', x);
                opponentCell.attr('data-y', y);
                opponentGrid.append(opponentCell);
            }
        }

        // Asignar eventos a la cuadrícula del oponente
        $('#opponentGrid .cell').click(function() {
            if (!isPlayerTurn || gameStatus !== 'playing') return;

            const x = $(this).data('x');
            const y = $(this).data('y');

            makeShot(x, y);
        });
    }

    // Actualizar puntuación
    function updateScore() {
        console.log('Actualizando puntuación...');
        $.ajax({
            url: 'api/games/score.php',
            method: 'GET',
            data: { game_id: gameId },
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.success) {
                    const playerScore = $('#playerScore');
                    const opponentScore = $('#opponentScore');
                    
                    if (playerScore.length && opponentScore.length) {
                        playerScore.text(`Tus barcos hundidos: ${response.player_sunk_ships}`);
                        opponentScore.text(`Barcos hundidos del oponente: ${response.opponent_sunk_ships}`);
                        
                        // Destacar el marcador del jugador que va ganando
                        if (response.player_sunk_ships > response.opponent_sunk_ships) {
                            playerScore.addClass('highlight');
                            opponentScore.removeClass('highlight');
                        } else if (response.opponent_sunk_ships > response.player_sunk_ships) {
                            opponentScore.addClass('highlight');
                            playerScore.removeClass('highlight');
                        } else {
                            playerScore.removeClass('highlight');
                            opponentScore.removeClass('highlight');
                        }
                    } else {
                        console.error('No se encontraron los elementos de puntuación');
                    }
                } else {
                    console.error('Error en la respuesta del servidor:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al actualizar la puntuación:', error);
            }
        });
    }

    function markSunkShip(ship, isPlayerShip) {
        const grid = isPlayerShip ? '#playerGrid' : '#opponentGrid';
        for (let i = 0; i < ship.size; i++) {
            const x = ship.orientation === 'horizontal' ? ship.start_x + i : ship.start_x;
            const y = ship.orientation === 'vertical' ? ship.start_y + i : ship.start_y;
            const cell = $(`${grid} .cell[data-x="${x}"][data-y="${y}"]`);
            cell.addClass('sunk');
            
            // Añadir un efecto visual adicional
            setTimeout(() => {
                cell.addClass('pulse');
                setTimeout(() => cell.removeClass('pulse'), 1000);
            }, i * 200);
        }
    }

    // Realizar un disparo
    function makeShot(x, y) {
        if (!isPlayerTurn || gameStatus !== 'playing') {
            console.log('No es tu turno o el juego no está en curso');
            return;
        }

        // Verificar si ya se ha disparado en esa posición
        if ($(`#opponentGrid .cell[data-x="${x}"][data-y="${y}"]`).hasClass('hit') || 
            $(`#opponentGrid .cell[data-x="${x}"][data-y="${y}"]`).hasClass('miss')) {
            console.log('Ya has disparado en esa posición');
            return;
        }

        $.ajax({
            url: 'api/games/shoot.php',
            method: 'POST',
            data: {
                game_id: gameId,
                x: x,
                y: y
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar la celda del oponente
                    const cell = $(`#opponentGrid .cell[data-x="${x}"][data-y="${y}"]`);
                    if (response.hit) {
                        cell.addClass('hit');
                        if (response.sunk) {
                            // Marcar el barco hundido
                            markSunkShip(response.ship, false);
                            // Reproducir sonido de barco hundido
                            const sunkSound = new Audio('assets/sounds/sunk.mp3');
                            sunkSound.play().catch(error => console.error('Error al reproducir sonido:', error));
                            // Mostrar mensaje de barco hundido 
                            showMessage(`¡Has hundido un ${response.ship.ship_type} de tamaño ${response.ship.size}!`);
                        } else {
                            // Reproducir sonido de impacto
                            const hitSound = new Audio('assets/sounds/hit.mp3');
                            hitSound.play().catch(error => console.error('Error al reproducir sonido:', error));
                            showMessage('¡Impacto!');
                        }
                    } else {
                        cell.addClass('miss');
                        // Reproducir sonido de fallo
                        const missSound = new Audio('assets/sounds/miss.mp3');
                        missSound.play().catch(error => console.error('Error al reproducir sonido:', error));
                        showMessage('Agua...');
                    }

                    // Actualizar puntuación después de cada disparo
                    updateScore();

                    // Actualizar estado del juego
                    if (response.game_status === 'playing') {
                        isPlayerTurn = false;
                        updateTurnIndicator();
                        
                        // Si es contra la IA, esperar un momento antes de que dispare
                        if (isAgainstAI) {
                            setTimeout(makeAITurn, 1500);
                        }
                    } else if (response.game_status === 'finished') {
                        gameStatus = 'finished';
                        updateTurnIndicator();
                        let winnerMessage;
                        if (response.winner_id === userId) {
                            winnerMessage = '¡Felicidades! ¡Has ganado!';
                        } else if (response.winner_id === -1) {
                            winnerMessage = '¡Has perdido! La IA ha ganado.';
                        } else if (response.winner_id !== -1 && response.winner_id !== userId ) {
                            winnerMessage = '¡Has perdido! Tu oponiente ha ganado!';
                        } else {
                            winnerMessage = '¡Empate!';
                        }
                        showGameResult(winnerMessage);
                    }
                } else {
                    console.error('Error en el disparo:', response.message);
                    showMessage(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición:', error);
                showMessage('Error al realizar el disparo');
            }
        });
    }

    function showMessage(message, isGameOver = false) {
        const messageDiv = $('#gameMessage');
        messageDiv.text(message).fadeIn(200);
        
        if (!isGameOver) {
            messageDiv.delay(2000).fadeOut(200);
        }
    }

    function showGameResult(message) {
        $('body').append(`
            <div class="game-overlay"></div>
            <div class="game-result">
                ${message}
                <div class="mt-3">
                    <button onclick="window.location.href='game.php'" class="btn btn-primary">Volver a Partidas</button>
                </div>
            </div>
        `);
        $('.game-overlay, .game-result').fadeIn(200);
    }

    // Turno de la IA
    function makeAITurn() {
        if (isPlayerTurn || gameStatus !== 'playing') return;

        // Verificar si hay celdas disponibles para disparar
        let availableCells = [];
        for (let x = 0; x < gridSize; x++) {
            for (let y = 0; y < gridSize; y++) {
                const cell = $(`#playerGrid .cell[data-x="${x}"][data-y="${y}"]`);
                if (!cell.hasClass('hit') && !cell.hasClass('miss')) {
                    availableCells.push({x, y});
                }
            }
        }

        if (availableCells.length === 0) {
            console.log('No hay más celdas disponibles para disparar');
            return;
        }

        // Elegir una celda aleatoria de las disponibles
        const randomIndex = Math.floor(Math.random() * availableCells.length);
        const {x, y} = availableCells[randomIndex];

        $.ajax({
            url: 'api/games/shoot.php',
            method: 'POST',
            data: {
                game_id: gameId,
                x: x,
                y: y,
                is_ai: true
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar la celda del jugador
                    const cell = $(`#playerGrid .cell[data-x="${x}"][data-y="${y}"]`);
                    if (response.hit) {
                        cell.addClass('hit');
                        if (response.sunk) {
                            // Marcar el barco hundido
                            markSunkShip(response.ship, true);
                            // Reproducir sonido de barco hundido
                            const sunkSound = new Audio('assets/sounds/sunk.mp3');
                            sunkSound.play().catch(error => console.error('Error al reproducir sonido:', error));
                            showMessage(`¡La IA ha hundido uno de tus barcos de tamaño ${response.ship.size}!`);
                        } else {
                            // Reproducir sonido de impacto
                            const hitSound = new Audio('assets/sounds/hit.mp3');
                            hitSound.play().catch(error => console.error('Error al reproducir sonido:', error));
                            showMessage('¡La IA ha impactado en uno de tus barcos!');
                        }
                    } else {
                        cell.addClass('miss');
                        // Reproducir sonido de fallo
                        const missSound = new Audio('assets/sounds/miss.mp3');
                        missSound.play().catch(error => console.error('Error al reproducir sonido:', error));
                        showMessage('La IA ha fallado su disparo');
                    }

                    // Actualizar puntuación después de cada disparo de la IA
                    updateScore();

                    // Actualizar el estado del juego
                    if (response.game_status === 'playing') {
                        isPlayerTurn = true;
                        updateTurnIndicator();
                    } else if (response.game_status === 'finished') {
                        gameStatus = 'finished';
                        updateTurnIndicator();
                        let winnerMessage;
                        if (response.winner_id === userId) {
                            winnerMessage = '¡Felicidades! ¡Has ganado!';
                        } else if (response.winner_id === -1) {
                            winnerMessage = '¡Has perdido! La IA ha ganado.';
                        } else {
                            winnerMessage = '¡Empate!';
                        }
                        showGameResult(winnerMessage);
                    }
                } else {
                    console.error('Error en el disparo de la IA:', response.message);
                }
            }
        });
    }

    // Cargar mensajes
    function loadMessages() {
        $.ajax({
            url: 'api/chat/messages.php',
            method: 'GET',
            data: { game_id: gameId },
            success: function(response) {
                if (response.success) {
                    const messagesDiv = $('#messages');
                    messagesDiv.empty();

                    response.messages.forEach(function(message) {
                        const messageDiv = $('<div class="message">');
                        messageDiv.text(`${message.username}: ${message.message}`);
                        messagesDiv.append(messageDiv);
                    });

                    messagesDiv.scrollTop(messagesDiv[0].scrollHeight);
                }
            }
        });
    }

    // Enviar mensaje
    $('#messageForm').submit(function(e) {
        e.preventDefault();
        const message = $('#messageInput').val().trim();
        if (message === '') return;

        $.ajax({
            url: 'api/chat/send.php',
            method: 'POST',
            data: {
                game_id: gameId,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    $('#messageInput').val('');
                    loadMessages();
                }
            }
        });
    });

    // Volver a la lista de partidas
    $('#backToGames').click(function() {
        window.location.href = 'game.php';
    });

    // Cargar barcos del jugador
    function loadPlayerShips() {
        $.ajax({
            url: 'api/games/ships.php',
            method: 'GET',
            data: { 
                game_id: gameId,
                player_id: userId
            },
            success: function(response) {
                if (response.success) {
                    response.ships.forEach(function(ship) {
                        for (let i = 0; i < ship.size; i++) {
                            const x = ship.orientation === 'horizontal' ? ship.start_x + i : ship.start_x;
                            const y = ship.orientation === 'vertical' ? ship.start_y + i : ship.start_y;
                            const cell = $(`#playerGrid .cell[data-x="${x}"][data-y="${y}"]`);
                            cell.addClass('ship');
                        }
                    });
                }
            }
        });
    }

    // Solicitar permiso para notificaciones
    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission();
    }

    // Manejar el botón de pausa
    $('#pauseGame').click(function() {
        isPaused = !isPaused;
        const button = $(this);
        if (isPaused) {
            button.html('<i class="fas fa-play"></i> Reanudar');
            button.removeClass('btn-warning').addClass('btn-success');
            $('#turnIndicator').addClass('alert-secondary');
        } else {
            button.html('<i class="fas fa-pause"></i> Pausar');
            button.removeClass('btn-success').addClass('btn-warning');
            $('#turnIndicator').removeClass('alert-secondary');
            updateTurnIndicator();
        }
    });
        // Manejar el botón de debug
        $('#debugEndGame').click(function() {
            if (confirm('¿Estás seguro de que quieres terminar el juego en modo debug?')) {
                $.ajax({
                    url: 'api/games/debug_end.php',
                    method: 'POST',
                    data: {
                        game_id: gameId
                    },
                    success: function(response) {
                        if (response.success) {
                            gameStatus = 'finished';
                            updateTurnIndicator();
                            showGameResult('¡Has ganado! (Modo Debug)');
                        } else {
                            console.log(response.message);
                            showMessage(response.message);
                        }
                    }
                });
            }
        });
 
    // Cargar información inicial
    loadGameInfo();
    loadMessages();

    // Actualizar información cada 5 segundos
    setInterval(function() {
        loadGameInfo();
        loadMessages();
    }, 5000);

    function checkShipSunk(gameId, x, y, isPlayer) {
        $.ajax({
            url: 'api/games/check_sunk.php',
            method: 'POST',
            data: {
                game_id: gameId,
                x: x,
                y: y,
                is_player: isPlayer
            },
            success: function(response) {
                if (response.success && response.sunk) {
                    const sunkSound = new Audio('assets/sounds/sunk.mp3');
                    sunkSound.play();
                    
                    // Mostrar mensaje de barco hundido con el tipo
                    const message = `¡Barco hundido! ${response.ship_type} de ${response.size} casillas`;
                    $('#gameMessage').html(`
                        <div class="alert alert-danger animate__animated animate__bounceIn">
                            ${message}
                        </div>
                    `).show().delay(3000).fadeOut();

                    // Actualizar contadores de barcos hundidos
                    updateScore();
                }
            }
        });
    }

    function showGameReport(game) {
        const isPlayer1 = game.player1_id == userId;
        const isPlayer2 = game.player2_id == userId;
        const isWinner = (isPlayer1 && game.winner_id == userId) || 
                        (isPlayer2 && game.winner_id == userId);
        
        // Crear el contenido del informe
        let reportContent = `
            <div class="game-report">
                <h3>${isWinner ? '¡Felicidades! Has ganado' : 'Fin del juego'}</h3>
                <div class="report-stats">
                    <div class="stat">
                        <span class="stat-label">Tus barcos hundidos:</span>
                        <span class="stat-value">${isPlayer1 ? game.player1_sunk_ships : game.player2_sunk_ships}</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Barcos hundidos del oponente:</span>
                        <span class="stat-value">${isPlayer1 ? game.player2_sunk_ships : game.player1_sunk_ships}</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Total de disparos:</span>
                        <span class="stat-value">${game.total_shots}</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Precisión:</span>
                        <span class="stat-value">${calculateAccuracy(game)}%</span>
                    </div>
                </div>
                <div class="report-actions">
                    <button class="btn btn-primary" onclick="location.href='game.php'">Volver al menú</button>
                    <button class="btn btn-secondary" onclick="location.reload()">Ver tableros completos</button>
                </div>
            </div>
        `;
        
        // Mostrar el informe en un modal
        const modal = `
            <div class="modal fade" id="gameReportModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            ${reportContent}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Añadir el modal al DOM y mostrarlo
        $('body').append(modal);
        $('#gameReportModal').modal('show');
        
        // Eliminar el modal cuando se cierre
        $('#gameReportModal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }

    function calculateAccuracy(game) {
        const isPlayer1 = game.player1_id == userId;
        const totalHits = isPlayer1 ? game.player2_sunk_ships : game.player1_sunk_ships;
        const totalShots = game.total_shots;
        
        if (totalShots === 0) return 0;
        return Math.round((totalHits / totalShots) * 100);
    }

    function updateBoards(game) {
        // Implementa la lógica para actualizar los tableros del juego
    }

    function updateShipCounters(game) {
        // Implementa la lógica para actualizar los contadores de barcos hundidos
    }

    function showGameMessage(message) {
        // Implementa la lógica para mostrar un mensaje en el juego
    }
}); 