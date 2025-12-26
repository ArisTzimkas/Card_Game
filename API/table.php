<?php
require_once 'game.php';
require_once 'dbconnect.php';
function table($input) {
    global $mysqli;

    $side = current_player($input['token']);
    if($side) {
        show_table_by_player($side);
    } else {
        header('Content-type: application/json');
        echo json_encode(show_table(), JSON_PRETTY_PRINT);
    } 
}

function show_table_by_player($token) {
    global $mysqli;
    $side = current_player($token);
    $table = show_table();       // φύλλα στο τραπέζι
    $hand  = show_hand($side);   // φύλλα του συγκεκριμένου παίκτη

    $result = [
        "table_cards" => $table,
        "my_hand"     => $hand
    ];

    header('Content-type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}

function show_table() {
    global $mysqli;
    $sql = 'SELECT * FROM cards WHERE location="TABLE" ORDER BY id ASC';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

function show_hand($side) {
    global $mysqli;
    $sql = 'SELECT * FROM cards WHERE location=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s',$side);
    $st->execute();
    $res = $st->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

function reset_game() {
    global $mysqli;
    $sql = 'call reset_game()';
    $st = $mysqli->prepare($sql);
    $st->execute();
}


function deal(){
    global $mysqli;

    $status = read_status();

    if($status['status'] != 'Started') {
        header("HTTP/1.1 403 Forbidden");
        echo json_encode(['errormesg'=>"Game not started."]);
        exit;
    }

    $st = $mysqli->prepare("CALL shuffled4()");
    $st->execute();
    $res = $st->get_result();
    $cards = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    //Καθάρισε extra result sets (ΠΟΛΥ ΣΗΜΑΝΤΙΚΟ)
    while ($mysqli->more_results() && $mysqli->next_result()) {;}

    if(!$cards || count($cards) < 4) {
        echo json_encode(['error'=>"Not enough cards in deck"]);
        exit;
    }

    // 4. Αρχή παιχνιδιού → p_turn = ""
    if($status["p_turn"] == "") {

        foreach($cards as $c) {
            $mysqli->query("UPDATE cards SET location='TABLE' WHERE id=".$c['id']);
        }

        echo json_encode([
            'status'=>"Cards dealt to table",
            'table_cards'=> $cards
        ], JSON_PRETTY_PRINT);
        //set p_turn = 'PLAYER1'
        $mysqli->query("UPDATE game_status SET p_turn='PLAYER1'");
    }

    // Deal 4 to PLAYER1
    $st = $mysqli->prepare("CALL shuffled4()");
    $st->execute();
    $res = $st->get_result();
    $p1_cards = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    while ($mysqli->more_results() && $mysqli->next_result()) {;}

    foreach($p1_cards as $c) {
        $mysqli->query("UPDATE cards SET location='PLAYER1' WHERE id=".$c['id']);
    }

    // Deal 4 to PLAYER2
    $st = $mysqli->prepare("CALL shuffled4()");
    $st->execute();
    $res = $st->get_result();
    $p2_cards = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    while ($mysqli->more_results() && $mysqli->next_result()) {;}

    foreach($p2_cards as $c) {
        $mysqli->query("UPDATE cards SET location='PLAYER2' WHERE id=".$c['id']);
    }

    echo json_encode([
        'status'=> 'success',
        'message'=> "Cards dealt to both players",
        'player1_cards'=> $p1_cards,
        'player2_cards'=> $p2_cards
    ], JSON_PRETTY_PRINT);
}


function play_card($token, $card_id) {
    global $mysqli;

    $side = current_player($token);
    if(!$side) {
        header('Content-type: application/json');
        echo json_encode([
            "status"=> "error",
            "message"=> "Invalid token"
        ]);
        return;
    }

    $sql = 'UPDATE cards SET location="TABLE" WHERE id=? AND location=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('is',$card_id,$side);
    $st->execute();

    if($st->affected_rows > 0) {
        header('Content-type: application/json');
        echo json_encode([
            "status"=> "success",
            "message"=> "Card played"
        ]);
        $next=($side=='PLAYER1')?'PLAYER2':'PLAYER1';
        $sql = "UPDATE game_status SET p_turn='$next'";
        $st2 = $mysqli->prepare($sql);
        $st2->execute();
    } else {
        header('Content-type: application/json');
        echo json_encode([
            "status"=> "error",
            "message"=> "Card not found in player's hand"
        ]);
    }
}