<?php

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

function show_table_by_player($side) {
    global $mysqli;

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
    $sql = 'SELECT * FROM cards WHERE location="table"';
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

function reset_table() {
    global $mysqli;
    $sql = 'call clear_table()';// TODO stored procedure in DB UPDATE cards SET location="deck"
    $st = $mysqli->prepare($sql);
    $st->execute();
}