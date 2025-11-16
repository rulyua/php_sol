<?php
header('Content-Type: application/json');

$env_path = __DIR__ . '/../.env';
if (!file_exists($env_path)) {
    echo json_encode(["error" => ".env file not found. Create .env in project root."]);
    exit;
}
$env = parse_ini_file($env_path);

$RPC = escapeshellarg($env['RPC_URL'] ?? 'http://127.0.0.1:8545');
$PK = escapeshellarg($env['PRIVATE_KEY'] ?? '');
$CONTRACT = escapeshellarg($env['CONTRACT'] ?? '');

$action = $_GET['action'] ?? null;

if (!$action) {
    echo json_encode(["error" => "Missing action"]);
    exit;
}

switch ($action) {

    case "sendEther":
        $cmd = "cast send $CONTRACT --value 1ether --private-key $PK --rpc-url $RPC 2>&1";
        break;

    case "balance":
        $cmd = "cast call $CONTRACT \"balance()(uint256)\" --rpc-url $RPC 2>&1";
        break;

    case "withdraw":
        $amount = $_GET['amount'] ?? "1000000000000000000";
        $amount = escapeshellarg($amount);
        $cmd = "cast send $CONTRACT \"withdraw(uint256)\" $amount --private-key $PK --rpc-url $RPC 2>&1";
        break;

    default:
        echo json_encode(["error" => "Unknown action"]);
        exit;
}

$output = null;
$return_var = null;
exec($cmd, $output, $return_var);
$response = [
    "action" => $action,
    "command" => $cmd,
    "exit_code" => $return_var,
    "output" => array_values($output)
];
echo json_encode($response);
