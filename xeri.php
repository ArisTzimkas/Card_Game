<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require_once "API/users.php";
require_once "API/game.php";

// Πάρε το action από το URL
$action = $_GET['action'] ?? null;

// Πάρε το HTTP method (GET, PUT, POST, DELETE)
$method = $_SERVER['REQUEST_METHOD'];

// Πάρε JSON input (για PUT/POST)
$input = json_decode(file_get_contents('php://input'), true);

// Routing
switch($action) {

    case 'user': 
        // PUT /xeri.php?action=user
        // GET /xeri.php?action=user&side=PLAYER1
        handle_user($method, $_GET['side'] ?? null, $input);
        break;

    case 'users':
        // GET /xeri.php?action=users
        show_users();
        break;

    case 'status':
        // GET /xeri.php?action=status
        show_status();
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}