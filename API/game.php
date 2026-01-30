<?php
require_once "dbconnect.php";

function show_status() {
    global $mysqli;
    update_game_status();

    $sql = "SELECT * FROM game_status";
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();

    header('Content-type: application/json');
    echo json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function update_game_status() {
    global $mysqli;

    // Get current status
    $sql = "SELECT * FROM game_status";
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    $status = $res->fetch_assoc();

    $new_status = null;
    $new_turn = null;

    // Check for aborted players
    $st3 = $mysqli->prepare(
        "SELECT count(*) AS Aborted 
         FROM players 
         WHERE last_action < (NOW() - INTERVAL 10 MINUTE)"
    );
    $st3->execute();
    $res3 = $st3->get_result();
    $aborted = $res3->fetch_assoc()['Aborted'];

    if ($aborted > 0) {
        $sql = "UPDATE players 
                SET username=NULL, token=NULL 
                WHERE last_action < (NOW() - INTERVAL 10 MINUTE)";
        $st2 = $mysqli->prepare($sql);
        $st2->execute();

        $new_status = 'Aborted';
    }

    // Count active players
    $sql = "SELECT count(*) AS c 
            FROM players 
            WHERE username IS NOT NULL";
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    $active_players = $res->fetch_assoc()['c'];

    if ($aborted == 0) {
    switch ($active_players) {
        case 0: $new_status = 'Not active'; break;
        case 1: $new_status = 'Initialized'; break;
        case 2: $new_status = 'Started'; break;
        }
    }

    // Keep old values if unchanged
    $new_status = $new_status ?? $status['status'];
    $new_turn   = $new_turn   ?? $status['p_turn'];

    // Update DB
    $sql = "UPDATE game_status 
            SET status=?, p_turn=?";
    $st = $mysqli->prepare($sql);
    $st->bind_param('ss', $new_status, $new_turn);
    $st->execute();
}

function read_status() {
	global $mysqli;
	
	$sql = 'select * from game_status';
	$st = $mysqli->prepare($sql);

	$st->execute();
	$res = $st->get_result();
	$status = $res->fetch_assoc();
	return($status);
}