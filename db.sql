CREATE TABLE cards (
	id TINYINT(1) AUTO_INCREMENT,
	suit ENUM('♠','♥','♣','♦') NOT NULL,
	card_value ENUM('A','2','3','4','5','6','7','8','9','10','J','Q','K') NOT NULL,
	location ENUM('DECK','TABLE','PLAYER1','PLAYER2','CAPTURED1','CAPTURED2') NOT NULL,
	PRIMARY KEY (id)	
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE players (
	username varchar(20) DEFAULT NULL,
	side ENUM('PLAYER1','PLAYER2') NOT NULL,
	token varchar(50) DEFAULT NULL,
	last_action timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	PRIMARY KEY (side)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE game_status (
	status ENUM('Not active','Initialized','Started','Ended','Aboarted') NOT NULL DEFAULT 'Not active',
	p_turn ENUM('PLAYER1','PLAYER2'),
	score_p1 TINYINT(1) DEFAULT 0,
	score_p2 TINYINT(1) DEFAULT 0,
	last_change timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



DELIMITER ;;
CREATE PROCEDURE reset_game()
BEGIN
  TRUNCATE TABLE cards;
  INSERT INTO cards (suit, card_value, location)
  SELECT s, v, 'DECK'
  FROM (
    SELECT '♠' AS s UNION ALL SELECT '♥' UNION ALL SELECT '♣' UNION ALL SELECT '♦'
  ) suits
  CROSS JOIN (
    SELECT 'A' AS v UNION ALL SELECT '2' UNION ALL SELECT '3' UNION ALL SELECT '4'
    UNION ALL SELECT '5' UNION ALL SELECT '6' UNION ALL SELECT '7' UNION ALL SELECT '8'
    UNION ALL SELECT '9' UNION ALL SELECT '10' UNION ALL SELECT 'J' UNION ALL SELECT 'Q'
    UNION ALL SELECT 'K'
  ) r;
  UPDATE game_status 
  SET status='Initialized', p_turn=NULL, score_p1=0, score_p2=0;
  UPDATE players 
  SET username=NULL, token=NULL;
END
DELIMITER ;



DELIMITER ;;
CREATE PROCEDURE shuffled4()
BEGIN
    SELECT c.id, c.suit, c.card_value
    FROM cards c
    WHERE location='DECK'
    ORDER BY RAND()
    LIMIT 4;
END
DELIMITER ;
