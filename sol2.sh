#!/usr/bin/env bash
set -e

PROJECT="foundry_cast_api"
rm -rf "$PROJECT"
mkdir -p "$PROJECT/public"

cat > "$PROJECT/.env" <<'ENV'
RPC_URL=http://127.0.0.1:8545
PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
CONTRACT=0x5FbDB2315678afecb367f032d93F642f64180aa3
ENV

cat > "$PROJECT/public/api.php" <<'PHP'
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
PHP

cat > "$PROJECT/public/index.php" <<'HTML'
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Contract Control (cast proxy)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<div class="container">
  <h2>Smart Contract Controls (cast proxy)</h2>

  <div class="mb-3">
    <button id="send" class="btn btn-primary">Send 1 ETH to contract</button>
    <button id="withdraw" class="btn btn-warning">Withdraw 1 ETH</button>
    <button id="balance" class="btn btn-success">Check Balance</button>
  </div>

  <pre id="output" class="mt-3 bg-dark text-light p-3 rounded"></pre>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function call(action, params) {
  params = params || {};
  params.action = action;
  $.get('api.php', params, function(res){
    try {
      var obj = (typeof res === 'string') ? JSON.parse(res) : res;
      $('#output').text(JSON.stringify(obj, null, 2));
    } catch (e) {
      $('#output').text(String(res));
    }
  }).fail(function(xhr){
    $('#output').text('Request failed: ' + xhr.responseText);
  });
}

$('#send').click(function(){ call('sendEther'); });
$('#withdraw').click(function(){ call('withdraw'); });
$('#balance').click(function(){ call('balance'); });
</script>
</body>
</html>
HTML

zip -r "foundry_cast_api.zip" "$PROJECT" >/dev/null
echo "Created foundry_cast_api.zip"

