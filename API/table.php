<?php
require_once 'game.php';
require_once 'dbconnect.php';
function table($token) {
    global $mysqli;
    if($token) {
        show_table_by_player($token);
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
        "Τραπέζι" => $table,
        "Χέρι παίκτη" => $hand
    ];

    header('Content-type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}

function show_table() {
    global $mysqli;
    $sql = 'SELECT suit,card_value FROM cards WHERE location="TABLE" ORDER BY `order` DESC LIMIT 1';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

function show_hand($side) {
    global $mysqli;

    $sql = 'CALL player_cards(?)';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s', $side);
    $st->execute();
    $res = $st->get_result();
    $data = $res->fetch_all(MYSQLI_ASSOC);
    clear_procedure_results($mysqli);
    return $data;
}

function reset_game() {
    global $mysqli;
    $sql = 'call reset_game()';
    $st = $mysqli->prepare($sql);
    $st->execute();
}

function deal(){
    global $mysqli;

    $response = [];
    
    $status = read_status();

    if($status['status'] != 'Started') {
        return ["error" => "Το παιχνίδι δεν ξεκίνησε."];
    }

    //τσεκαρισμα αν υπαρχουν 12 χαρτια για να μοιραστουν στους παικτες
    $remain = $mysqli->query("SELECT COUNT(*) AS c FROM cards WHERE location='DECK'")->fetch_assoc()['c'];
    if($remain<12){
        if($status['p_turn']=='PLAYER1'){
            $last='CAPTURED1';
        }
        else{
            $last= 'CAPTURED2';
        }
        $st = $mysqli->prepare("UPDATE cards SET location=? WHERE location='TABLE'");   //δινει στον τελευταιο παικτη τα χαρτια που εμειναν στο τραπεζι
        $st->bind_param("s", $last);
        $st->execute();

        $mysqli->query("UPDATE game_status SET status='Ended'");
        $points = points();

        return [
            "status" => "Ended",
            "points" => $points
        ];
    }

    

    //καθάρισε extra result sets
    clear_procedure_results($mysqli);

    // αρχή παιχνιδιού με p_turn = ""
    $next_order = $mysqli->query("SELECT IFNULL(MAX(`order`),0)+1 AS n FROM cards")->fetch_assoc()['n'];
    if($status["p_turn"] == "") {
        $st = $mysqli->prepare("CALL shuffled4()");
        $st->execute();
        $res = $st->get_result();
        $cards = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

        clear_procedure_results($mysqli);

        foreach($cards as $c) {
            $mysqli->query("UPDATE cards SET location='TABLE' , `order`=$next_order WHERE id=".$c['id']);
            $next_order++;
        }

        $response["Μοίρασμα στο τραπέζι"] = $cards;

        //set p_turn = 'PLAYER1'
        $mysqli->query("UPDATE game_status SET p_turn='PLAYER1'");
    }

    // Deal 6 to PLAYER1
    $st = $mysqli->prepare("CALL shuffled6()");
    $st->execute();
    $res = $st->get_result();
    $p1_cards = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    while ($mysqli->more_results() && $mysqli->next_result()) {;}

    foreach($p1_cards as $c) {
        $mysqli->query("UPDATE cards SET location='PLAYER1' WHERE id=".$c['id']);
    }

    // Deal 6 to PLAYER2
    $st = $mysqli->prepare("CALL shuffled6()");
    $st->execute();
    $res = $st->get_result();
    $p2_cards = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    while ($mysqli->more_results() && $mysqli->next_result()) {;}

    foreach($p2_cards as $c) {
        $mysqli->query("UPDATE cards SET location='PLAYER2' WHERE id=".$c['id']);
    }

    $response["Μοίρασμα στους παίκτες"] = [
        "Χαρτιά 1ου παίκτη" => $p1_cards,
        "Χαρτιά 2ου παίκτη" => $p2_cards
    ];
    return $response;

}


function play_card($token, $card_id) {
    global $mysqli;

    $side = current_player($token);
    $status=read_status();

    $response = [];

    if(!$side) {
        $response["error"] = "Άκυρο Token";
        output($response);
        return;
    }
    if($side!=$status["p_turn"]) {
        $response["error"] = "Δεν έχεις σειρά";
        output($response);
        return;
    }
    $next_order = $mysqli->query("SELECT IFNULL(MAX(`order`),0)+1 AS n FROM cards")->fetch_assoc()['n'];
    $sql = 'UPDATE cards SET location="TABLE", `order`=? WHERE id=? AND location=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('iis',$next_order,$card_id,$side);
    $st->execute();

    if($st->affected_rows > 0) {
        $response["Μήνυμα"] = "Η κάρτα παίχτηκε";
    } else {
        $response["Error"] = "Ο παίκτης δεν έχει την κάρτα";
        output($response);
        return;
    }
    
    $rule_messages = rule_check($side, $card_id);
    if (!empty($rule_messages)) {
        $response["Κανόνες παιχνιδιού"] = $rule_messages;
    }

    if($side=='PLAYER2'){
        $st = $mysqli->query("SELECT COUNT(*) AS c FROM cards WHERE location='PLAYER2'")->fetch_assoc()['c'];
        if($st==0){
            $deal_result = deal();   // ΠΑΙΡΝΕΙΣ ARRAY
            $response["Δοκιμή μοιράσματος χαρτιών"] = $deal_result;

            // Αν τελείωσε το παιχνίδι, κάνε output εδώ και σταμάτα
            if (isset($deal_result["status"]) && $deal_result["status"] == "Ended") {
                output($response);
                return;
            }
        }
    }

    $next = ($side=='PLAYER1') ? 'PLAYER2' : 'PLAYER1';
    $mysqli->query("UPDATE game_status SET p_turn='$next'");
    $response["Σειρά έχει τώρα ο"] = "$next";

    output($response);
    return;
}

function rule_check($side,$card_id) {
    global $mysqli;
    $messages = [];

    //take the last 2 cards from table 
    //TODO na to kanw stored procedure 
    $sql = 'SELECT suit,card_value FROM cards WHERE location="TABLE" ORDER BY `order` DESC LIMIT 2';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    $row=$res->fetch_all(MYSQLI_ASSOC);

    //take the count of table cards
    //TODO na to kanw stored procedure 
    $sql2 = 'SELECT COUNT(*) FROM cards WHERE location="TABLE" ';
    $st2 = $mysqli->prepare($sql2);
    $st2->execute();
    $res2 = $st2->get_result();
    $count=$res2->fetch_assoc()["COUNT(*)"];

    if($side== 'PLAYER1') {
        $location='CAPTURED1';
        $location2= 'score_p1';
    } else {
        $location= 'CAPTURED2';
        $location2= 'score_p2';
    }
    //TODO na to kanw stored procedure
    $sql = 'UPDATE cards SET location=? WHERE location="TABLE" ';

    $sql3="SELECT $location2 FROM game_status";
    $st3 = $mysqli->prepare($sql3);
    $st3->execute();
    $score= $st3->get_result()->fetch_assoc()[$location2];
    
    // xeri me bale(J)
    if($count==2 && ($card_id==41|| $card_id==42 || $card_id==43 || $card_id==44)) {
        $st = $mysqli->prepare($sql);
        $st->bind_param('s',$location);
        $st->execute();

        $score=$score+20;
        $sql2 = "UPDATE game_status SET $location2=?";
        $st2 = $mysqli->prepare($sql2);
        $st2->bind_param('i',$score);
        $st2->execute();

        $messages[] = ["message"=> "Έκανες ξερή με βαλέ +20"];
    }

    //xeri me idio card_value
    if($count==2 && ($row[0]['card_value']==$row[1]['card_value'])){
        $st = $mysqli->prepare($sql);
        $st->bind_param('s',$location);
        $st->execute();

        $score=$score+10;
        $sql2 = "UPDATE game_status SET $location2=?";
        $st2 = $mysqli->prepare($sql2);
        $st2->bind_param('i',$score);
        $st2->execute();

        $messages[] = ["message"=> "Έκανες ξερή +10"];
    }

    // mazema me bale(J)
    if($count>2 && ($card_id==41|| $card_id==42 || $card_id==43 || $card_id==44)) {
        $st = $mysqli->prepare($sql);
        $st->bind_param('s',$location);
        $st->execute();

        $messages[] = ["message"=> "Μάζεψες με βαλέ"];
    }

    //mazema me idio card_value
    if($count>2 && $row[0]['card_value']==$row[1]['card_value']){
        $st = $mysqli->prepare($sql);
        $st->bind_param('s',$location);
        $st->execute();

        $messages[] = ["message"=> "Μάζεψες με ίδιο νόυμερο-φιγούρα"];
    }

    return $messages;
}

function points(){
    global $mysqli;

    $st = $mysqli->prepare("CALL points_count()");
    $st->execute();

    clear_procedure_results($mysqli);

    $st2 = $mysqli->prepare("SELECT score_p1,score_p2 FROM game_status");
    $st2->execute();
    $res = $st2->get_result();
    $scores = $res->fetch_assoc();

    if ($scores['score_p1'] > $scores['score_p2']) {
        $winner = "PLAYER1";
    } elseif ($scores['score_p2'] > $scores['score_p1']) {
        $winner = "PLAYER2";
    } else {
        $winner = "DRAW";
    }

    return [
        "Πόντοι 1ου παίκτη" => $scores['score_p1'],
        "Πόντοι 2ου παίκτη" => $scores['score_p2'],
        "Νικητής" => $winner
    ];

}

function output($data) {
    header('Content-type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
}




function clear_procedure_results($mysqli) {
    while ($mysqli->more_results()) {
        $mysqli->next_result();
        $res = $mysqli->use_result();
        if ($res instanceof mysqli_result) {
            $res->free();
        }
    }
}