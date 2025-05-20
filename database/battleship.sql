-- Database: battleship

CREATE DATABASE IF NOT EXISTS battleship_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE battleship_db;


CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB ;

--
-- Struttura della tabella `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `player1_id` int(11) NOT NULL,
  `player2_id` int(11) DEFAULT NULL,
  `grid_size` int(11) NOT NULL,
  `status` enum('setup','waiting','playing','finished') DEFAULT 'setup',
  `game_type` enum('vs_ai','vs_player') NOT NULL DEFAULT 'vs_player',
  `current_player` int(11) DEFAULT NULL,
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `finished_at` timestamp NULL DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL
) ENGINE=InnoDB ;


--
-- Struttura della tabella `ships`
--

CREATE TABLE `ships` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `ship_type` enum('portaaviones','acorazado','crucero','submarino','destructor') NOT NULL,
  `size` int(11) NOT NULL,
  `start_x` int(11) NOT NULL,
  `start_y` int(11) NOT NULL,
  `orientation` enum('horizontal','vertical') NOT NULL
) ENGINE=InnoDB ;



--
-- Struttura della tabella `shots`
--

CREATE TABLE `shots` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `hit` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB ;



--
-- Struttura della tabella `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB ;

--
-- !IMPORTANTE! Dati user AI per la tabella `users` 
--
INSERT INTO `users` (`id`, `username`, `password`, `created_at`) VALUES
(-1, 'AI', '$2y$10$rCwapDpqt8OKOwfXC3A0eOdj5bVTulVflxpweNaI0b1rnFivhIk0W', '2025-04-13 10:13:16');


--
-- Indici per le tabelle `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indici per le tabelle `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player1_id` (`player1_id`),
  ADD KEY `player2_id` (`player2_id`),
  ADD KEY `winner_id` (`winner_id`);

--
-- Indici per le tabelle `ships`
--
ALTER TABLE `ships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indici per le tabelle `shots`
--
ALTER TABLE `shots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indici per le tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);


--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Limiti per la tabella `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `users` (`id`);

--
-- Limiti per la tabella `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `games_ibfk_1` FOREIGN KEY (`player1_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `games_ibfk_2` FOREIGN KEY (`player2_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `games_ibfk_3` FOREIGN KEY (`winner_id`) REFERENCES `users` (`id`);

--
-- Limiti per la tabella `ships`
--
ALTER TABLE `ships`
  ADD CONSTRAINT `ships_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  ADD CONSTRAINT `ships_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `users` (`id`);

--
-- Limiti per la tabella `shots`
--
ALTER TABLE `shots`
  ADD CONSTRAINT `shots_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  ADD CONSTRAINT `shots_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `users` (`id`);
COMMIT;


