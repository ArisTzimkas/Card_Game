<?php

function show_status() {
    global $mysqli;
    update_game_status();
    $sql = "select * from game_status";
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res=$st->get_result();
    header ('Content-type: application/json');
    echo json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function update_game_status() {
    global $mysqli;
	
	$sql = 'select * from game_status';
	$st = $mysqli->prepare($sql);
	$st->execute();
	$res = $st->get_result();
	$status = $res->fetch_assoc();
    
    $new_status=null;
	$new_turn=null;
	
    // Check for aborted players
	$st3=$mysqli->prepare('select count(*) as Aborted from players WHERE last_action< (NOW() - INTERVAL 20 MINUTE)');
	$st3->execute();
	$res3 = $st3->get_result();
	$aborted = $res3->fetch_assoc()['Aborted']; 
	if($aborted>0) {
		$sql = "UPDATE players SET username=NULL, token=NULL WHERE last_action< (NOW() - INTERVAL 20 MINUTE)";
		$st2 = $mysqli->prepare($sql);
		$st2->execute();
		if($status['status']=='Started') {
			$new_status='Aborted';
		}
    }

    // Check number of active players
    $sql = 'select count(*) as c from players where username is not null';
	$st = $mysqli->prepare($sql);
	$st->execute();
	$res = $st->get_result();
	$active_players = $res->fetch_assoc()['c'];
	
	switch($active_players) {
		case 0: $new_status='Not active'; break;
		case 1: $new_status='Initialized'; break;
		case 2: $new_status='Started'; 
			if($status['p_turn']==null) {
				$new_turn='PLAYER1'; // It was not started before...
			}
			break;
	}

	$sql = 'update game_status set status=?, p_turn=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('ss',$new_status,$new_turn);
	$st->execute();
}