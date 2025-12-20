<?php
require_once 'API/game.php';

// Main handler for users endpoint
function handle_user($method, $side, $input){
    if($method == 'GET'){
        show_user($side);
    } else if($method == 'PUT'){
        set_user($input);
    }
}

// Shows a specific user
function show_user($side) {
	global $mysqli;
	$sql = 'select username,side,token from players where side=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s',$side);
	$st->execute();
	$res = $st->get_result();
	header('Content-type: application/json');
	echo json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

// Shows all users
function show_users() {
	global $mysqli;
	$sql = 'select username,side,token from players';
	$st = $mysqli->prepare($sql);
	$st->execute();
	$res = $st->get_result();
	header('Content-type: application/json');
	echo json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

// checks and sets a user in the game
function set_user($input) {
    if(!isset($input['username']) || $input['username']=='') {
		header("HTTP/1.1 400 Bad Request");
		echo json_encode(['errormesg'=>"No username given."]);
		exit;
	}
	$username=$input['username'];
	global $mysqli;

	$sql = 'SELECT count(*) as c 
	        from players 
			where username is not null
			and last_action > (NOW() - INTERVAL 20 MINUTE)';
	$st = $mysqli->prepare($sql);
	$st->execute();
	$res = $st->get_result();
	$count = $res->fetch_assoc()['c'];
    if($count>=2) {
        header("HTTP/1.1 403 Forbidden");
        print json_encode(['errormesg'=>"Game is full."]);
        exit;
    }
    $slot = ($count==0)?'PLAYER1':'PLAYER2';

	// Set username, generate token and update last_action
    $sql = 'UPDATE players 
            SET username=?, token=MD5(CONCAT(?, NOW())), last_action=NOW() 
            WHERE side=?';
    $st2 = $mysqli->prepare($sql);
    $st2->bind_param('sss',$username,$username,$slot);
    $st2->execute();

    update_game_status();

	//gets token of the player
    $sql = 'SELECT token 
            FROM players 
            WHERE side=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s',$slot);
    $st->execute();
    $res = $st->get_result();

    header('Content-type: application/json');
    echo json_encode(['side'=>$slot,'token'=>$res->fetch_assoc()['token']], JSON_PRETTY_PRINT);
}

// Returns the current player side based on the provided token
function current_player($token) {
	global $mysqli;
	if($token==null) {return(null);}
	$sql = 'select * from players where token=?';
	$st = $mysqli->prepare($sql);
	$st->bind_param('s',$token);
	$st->execute();
	$res = $st->get_result();
	if($row=$res->fetch_assoc()) {
		return($row['side']);//returns player1 or player2
	}
	return(null);
}
