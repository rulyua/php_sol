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
        $deployCmd = "forge create src/Wallet.sol:Wallet --rpc-url $RPC --private-key $PK --broadcast 2>&1";
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
