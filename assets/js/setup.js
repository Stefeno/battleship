$(document).ready(function() {
    const gameId = new URLSearchParams(window.location.search).get('game_id');
    let gridSize = 10;
    let selectedShip = null;
    let placedShips = new Set();
    let isHorizontal = true;
    let shipCounts = {
        'portaaviones': 1,    // Portaaviones (5 casillas)
        'acorazado': 1,       // Acorazado (4 casillas)
        'crucero': 1,         // Crucero (3 casillas)
        'submarino': 1,       // Submarino (3 casillas)
        'destructor': 1       // Destructor (2 casillas)
    };

    // Cargar información del juego
    $.ajax({
        url: 'api/games/info.php',
        method: 'GET',
        data: { game_id: gameId },
        success: function(response) {
            if (response.success && response.game) {
                // Si el juego no está en setup, mostrar error
                if (response.game.status !== 'setup') {
                    alert('El juego no está en fase de configuración');
                    window.location.href = 'game.php';
                    return;
                }
                
                // Usar el tamaño de cuadrícula del juego
                gridSize = response.game.grid_size;
                
                // Inicializar la cuadrícula y los eventos
                initializeGrid();
                assignGridEvents();
                updateShipCounters();
            } else {
                // Si hay un error, mostrar el mensaje
                alert('Error: ' + (response.message || 'No se pudo cargar la información del juego'));
                window.location.href = 'game.php';
            }
        },
        error: function() {
            // Si hay un error en la petición, redirigir a game.php
            alert('Error al cargar la información del juego');
            window.location.href = 'game.php';
        }
    });

    // Inicializar cuadrícula
    function initializeGrid() {
        const grid = $('#gameGrid');
        grid.css('grid-template-columns', `repeat(${gridSize}, 1fr)`);
        grid.empty(); // Limpiar la cuadrícula antes de inicializar
        
        for (let y = 0; y < gridSize; y++) {
            for (let x = 0; x < gridSize; x++) {
                const cell = $('<div class="cell">');
                cell.attr('data-x', x);
                cell.attr('data-y', y);
                grid.append(cell);
            }
        }
    }

    // Verificar si se pueden colocar todos los barcos
    function canPlaceAllShips() {
        return Object.values(shipCounts).every(count => count === 0);
    }

    // Actualizar contadores de barcos
    function updateShipCounters() {
        for (const [ship, count] of Object.entries(shipCounts)) {
            $(`#${ship}Count`).text(count);
        }
        
        // Mostrar/ocultar botón de empezar partida
        if (canPlaceAllShips()) {
            $('#startGame').show();
        } else {
            $('#startGame').hide();
        }
    }

    // Seleccionar nave
    $('.ship-selector').click(function() {
        if ($(this).hasClass('disabled')) return;
        
        const shipType = $(this).attr('id');
        if (shipCounts[shipType] > 0) {
            selectedShip = shipType;
            $('.ship-selector').removeClass('selected');
            $(this).addClass('selected');
        }
    });

    // Cambiar orientación
    $('#rotateShip').click(function() {
        isHorizontal = !isHorizontal;
        $(this).text(isHorizontal ? '↔' : '↕');
    });

    // Asignar eventos a la cuadrícula
    function assignGridEvents() {
        $('#gameGrid .cell').click(function() {
            if (!selectedShip) return;

            const x = $(this).data('x');
            const y = $(this).data('y');
            const shipSize = getShipSize(selectedShip);

            if (canPlaceShip(x, y, shipSize, isHorizontal)) {
                placeShip(x, y, shipSize, isHorizontal);
                shipCounts[selectedShip]--;
                updateShipCounters();
                
                // Deseleccionar la nave después de colocarla
                selectedShip = null;
                $('.ship-selector').removeClass('selected');
            }
        });
    }

    // Verificar si se puede colocar la nave
    function canPlaceShip(x, y, size, horizontal) {
        if (horizontal) {
            if (x + size > 10) return false;
            for (let i = 0; i < size; i++) {
                if ($(`.cell[data-x="${x + i}"][data-y="${y}"]`).hasClass('ship')) {
                    return false;
                }
            }
        } else {
            if (y + size > 10) return false;
            for (let i = 0; i < size; i++) {
                if ($(`.cell[data-x="${x}"][data-y="${y + i}"]`).hasClass('ship')) {
                    return false;
                }
            }
        }
        return true;
    }

    // Colocar nave
    function placeShip(x, y, size, horizontal) {
        for (let i = 0; i < size; i++) {
            const cellX = horizontal ? x + i : x;
            const cellY = horizontal ? y : y + i;
            $(`.cell[data-x="${cellX}"][data-y="${cellY}"]`).addClass('ship');
        }

        // Guardar posición del barco
        $.ajax({
            url: 'api/games/place_ship.php',
            method: 'POST',
            data: {
                game_id: gameId,
                ship_type: selectedShip,
                ship_size: size,
                start_x: x,
                start_y: y,
                orientation: horizontal ? 'horizontal' : 'vertical'
            },
            success: function(response) {
                if (response.success) {
                    placedShips.add(selectedShip);
                    $(`.ship-selector[id="${selectedShip}"]`).addClass('placed');
                    
                    if (placedShips.size === 5) {
                        $('#startGame').prop('disabled', false);
                    }
                    
                    selectedShip = null;
                    $('.ship-selector').removeClass('selected');
                } else {
                    alert('Error: ' + response.message);
                    // Revertir colocación
                    for (let i = 0; i < size; i++) {
                        const cellX = horizontal ? x + i : x;
                        const cellY = horizontal ? y : y + i;
                        $(`.cell[data-x="${cellX}"][data-y="${cellY}"]`).removeClass('ship');
                    }
                }
            }
        });
    }

    // Obtener tamaño del barco
    function getShipSize(shipType) {
        const sizes = {
            'portaaviones': 5,
            'acorazado': 4,
            'crucero': 3,
            'submarino': 3,
            'destructor': 2
        };
        return sizes[shipType];
    }

    // Manejar clic en el botón de empezar partida
    $('#startGame').click(function() {
        $.ajax({
            url: 'api/games/start.php',
            method: 'POST',
            data: { game_id: gameId },
            success: function(response) {
                if (response.success) {
                    window.location.href = 'play.php?game_id=' + gameId;
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    });

    // Volver a la lista de partidas
    $('#backToGames').click(function() {
        window.location.href = 'game.php';
    });

    // Inicializar contadores
    updateShipCounters();
}); 