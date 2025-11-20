<?php
session_start();

// ---- Load .env ----
$envPath = __DIR__ . '/../.env'; // <-- updated path
if (!file_exists($envPath)) {
    die("<b>ERROR:</b> .env file missing at: $envPath");
}

$env = parse_ini_file($envPath, false, INI_SCANNER_RAW);

// ---- Required Variables ----
$privateKey  = $env['PRIVATE_KEY'] ?? null;
$rpcUrl      = $env['RPC_URL'] ?? "http://127.0.0.1:8545";
$foundryDir  = $env['FOUNDRY_PATH'] ?? __DIR__;
//echo '<pre>';print_r($foundryDir);echo '</pre>';die;
$contractRaw = $env['CONTRACT'] ?? null;

if (!$privateKey || !$contractRaw) {
    die("<b>ERROR:</b> PRIVATE_KEY or CONTRACT missing in .env");
}

// split CONTRACT (src/file.sol:Name|address)
$contractParts = explode("|", $contractRaw);
$contract      = $contractParts[0];

// ---- Deployment Logic ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deploy'])) {

		// Deployment command (now with --broadcast)
		$command = "cd {$foundryDir} && forge create --broadcast --rpc-url {$rpcUrl} --private-key {$privateKey} {$contract} 2>&1";

    exec($command, $output, $exitCode);

    // Save execution log
//    file_put_contents(__DIR__ . '/deploy_output.log', "COMMAND:\n$command\n\n" . implode("\n", $output));

    // Extract deployed address
    $address = "UNKNOWN";
    foreach ($output as $line) {
        if (preg_match('/Deployed to:\s*(0x[a-fA-F0-9]{40})/', $line, $match)) {
            $address = $match[1];
            break;
        }
    }

    // ---- Update CONTRACT line in .env ----
    if ($address !== "UNKNOWN") {

        // Backup
        copy($envPath, $envPath . '.bak');

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);

        foreach ($lines as &$line) {
            if (strpos($line, "CONTRACT=") === 0) {

                list($key, $value) = explode("=", $line, 2);

                if (strpos($value, "|") !== false) {
                    $parts = explode("|", $value);
                    $value = $parts[0]; // remove old address
                }

                $line = "CONTRACT=" . $value . "|" . $address;
            }
        }

        file_put_contents($envPath, implode("\n", $lines) . "\n");
    }

    // Store results for GET refresh
    $_SESSION['deployment'] = [
        'success'  => $exitCode === 0,
        'address'  => $address,
        'output'   => implode("\n", $output),
        'command'  => $command
    ];

    header("Location: deploy.php");
    exit;
}

// fetch stored results
$result = $_SESSION['deployment'] ?? null;
unset($_SESSION['deployment']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Contract Deployment</title>
    <style>
        body { font-family: Arial, sans-serif; padding:20px; }
        button { padding:10px 20px; font-size:16px; cursor:pointer; }
        pre { background:#111; color:#0f0; padding:15px; border-radius:5px; overflow-x:auto; }
        .success { color:green; font-weight:bold; }
        .error { color:red; font-weight:bold; }
    </style>
</head>
<body>

<h2>🚀 Deploy Smart Contract</h2>

<p><b>Contract:</b> <?= htmlspecialchars($contract) ?></p>

<?php if (isset($contractParts[1])): ?>
<p><b>Current Address:</b> <?= htmlspecialchars($contractParts[1]) ?></p>
<?php endif; ?>

<form method="POST">
    <button name="deploy">Deploy Contract</button>
</form>

<hr>

<?php if ($result): ?>
    <h3>Result:</h3>

    <?php if ($result['success']): ?>
        <p class="success">✔ Deployment Successful</p>
        <p><b>New Address:</b> <?= htmlspecialchars($result['address']) ?></p>
    <?php else: ?>
        <p class="error">❌ Deployment Failed</p>
    <?php endif; ?>

    <h4>Executed Command:</h4>
    <pre><?= htmlspecialchars($result['command']) ?></pre>

    <h4>Forge Output:</h4>
    <pre><?= htmlspecialchars($result['output']) ?></pre>
<?php endif; ?>

</body>
</html>
