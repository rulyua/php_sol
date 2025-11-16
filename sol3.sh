#!/usr/bin/env bash
set -e
PROJECT="foundry_php_forge_create"
rm -rf "$PROJECT"
mkdir -p "$PROJECT/public"

cat > "$PROJECT/.env" <<'ENV'
RPC_URL=http://127.0.0.1:8545
PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
CONTRACT=
ENV

cat > "$PROJECT/src_Wallet.sol" <<'SOL'
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

contract Wallet {
    address public owner;

    constructor() {
        owner = msg.sender;
    }

    receive() external payable {}

    function withdraw(uint256 amount) external {
        require(msg.sender == owner, "Not owner");
        payable(owner).transfer(amount);
    }

    function balance() public view returns(uint256) {
        return address(this).balance;
    }
}
SOL

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
$CONTRACT = trim($env['CONTRACT'] ?? '');

$action = $_GET['action'] ?? null;

if (!$action) {
    echo json_encode(["error" => "Missing action"]);
    exit;
}

switch ($action) {

    case "deploy":
        $cmd = "forge build 2>&1";
        exec($cmd, $out, $rc);
        $build = implode("\\n", $out);
        $deployCmd = "forge create src/Wallet.sol:Wallet --rpc-url $RPC --private-key $PK 2>&1";
        exec($deployCmd, $out2, $rc2);
        $deployOut = implode("\\n", $out2);
        if (preg_match('/0x[a-fA-F0-9]{40}/', $deployOut, $m)) {
            $address = $m[0];
            $envFile = __DIR__ . '/../.env';
            $content = file_get_contents($envFile);
            if (strpos($content, 'CONTRACT=') !== false) {
                $content = preg_replace('/CONTRACT=.*/', 'CONTRACT=' . $address, $content);
            } else {
                $content .= "\\nCONTRACT=" . $address . "\\n";
            }
            file_put_contents($envFile, $content);
            echo json_encode(["status"=>"success","address"=>$address,"raw"=>$deployOut]);
        } else {
            echo json_encode(["status"=>"error","raw"=>$deployOut]);
        }
        break;

    case "sendEther":
        $contract = escapeshellarg($env['CONTRACT'] ?? '');
        $cmd = "cast send $contract --value 1ether --private-key $PK --rpc-url $RPC 2>&1";
        exec($cmd, $out, $rc);
        echo json_encode(["cmd"=>$cmd,"output"=>implode("\\n",$out),"rc"=>$rc]);
        break;

    case "balance":
        $contract = escapeshellarg($env['CONTRACT'] ?? '');
        $cmd = "cast call $contract \"balance()(uint256)\" --rpc-url $RPC 2>&1";
        exec($cmd, $out, $rc);
        echo json_encode(["cmd"=>$cmd,"output"=>implode("\\n",$out),"rc"=>$rc]);
        break;

    case "withdraw":
        $amount = $_GET['amount'] ?? "1000000000000000000";
        $contract = escapeshellarg($env['CONTRACT'] ?? '');
        $amount = escapeshellarg($amount);
        $cmd = "cast send $contract \"withdraw(uint256)\" $amount --private-key $PK --rpc-url $RPC 2>&1";
        exec($cmd, $out, $rc);
        echo json_encode(["cmd"=>$cmd,"output"=>implode("\\n",$out),"rc"=>$rc]);
        break;

    default:
        echo json_encode(["error"=>"Unknown action"]);
        break;
}
PHP

cat > "$PROJECT/public/index.php" <<'HTML'
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Auto Deploy Wallet</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
  <h2>Auto-Deploy Wallet</h2>
  <div class="mb-3">
    <button id="deploy" class="btn btn-danger">🚀 Deploy Contract</button>
    <button id="send" class="btn btn-primary contract-action">Send 1 ETH</button>
    <button id="withdraw" class="btn btn-warning contract-action">Withdraw 1 ETH</button>
    <button id="balance" class="btn btn-success contract-action">Check Balance</button>
  </div>
  <pre id="output" class="mt-3 bg-dark text-light p-3 rounded"></pre>
  <div class="mt-3"><small>Make sure <code>forge</code> and <code>cast</code> are in PATH and Anvil is running.</small></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function call(action, params) {
  params = params || {};
  params.action = action;
  $.get('api.php', params, function(res){
    var obj = (typeof res === 'string') ? JSON.parse(res) : res;
    $('#output').text(JSON.stringify(obj, null, 2));
    if (obj.status === 'success' && obj.address) {
      $('.contract-action').prop('disabled', false);
    }
  }).fail(function(xhr){
    $('#output').text('Request failed: ' + xhr.responseText);
  });
}

$('#deploy').click(function(){ call('deploy'); });
$('#send').click(function(){ call('sendEther'); });
$('#withdraw').click(function(){ call('withdraw'); });
$('#balance').click(function(){ call('balance'); });

// disable contract buttons until deployed
$('.contract-action').prop('disabled', true);
</script>
</body>
</html>
HTML

zip -r "foundry_php_forge_create.zip" "$PROJECT" >/dev/null
echo "Created foundry_php_forge_create.zip"

