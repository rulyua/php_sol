<?php
// If form submitted, run deployment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $accountsPath = __DIR__ . '/../accounts.php';
    $accounts = require $accountsPath;

    $privateKey = $_POST['account'] ?? null;
    $contractPath = $_POST['contract_path'] ?? null;

    if (!$privateKey) {
        echo json_encode(['error' => 'Missing selected account']);
        exit;
    }
    if (!$contractPath) {
        echo json_encode(['error' => 'Missing contract_path']);
        exit;
    }

    // Find deployer address
    $deployerAddress = null;
    foreach ($accounts['accounts'] as $acc) {
        if ($acc['private_key'] === $privateKey) {
            $deployerAddress = $acc['address'];
            break;
        }
    }

    if (!$deployerAddress) {
        echo json_encode(['error' => 'Private key not found in accounts.php']);
        exit;
    }

    $rpcUrl = 'http://127.0.0.1:8545';

    $cmd = sprintf(
        "forge create --json  --broadcast --rpc-url %s --private-key %s %s 2>&1",
        escapeshellarg($rpcUrl),
        escapeshellarg($privateKey),
        escapeshellarg($contractPath)
    );

    exec($cmd, $outputLines, $exitCode);
    $outputText = implode("\n", $outputLines);

    // Extract deployed address
    $deployedAddress = null;
    if (preg_match('/"deployedTo"\s*:\s*"([^"]+)"/', $outputText, $m)) {
        $deployedAddress = $m[1];
    }
    if (!$deployedAddress && preg_match('/Deployed to:\s*(0x[0-9A-Fa-f]{40})/', $outputText, $m)) {
        $deployedAddress = $m[1];
    }

    if (!$deployedAddress) {
        echo json_encode([
            'error' => 'Could not extract deployed address',
            'output' => $outputLines,
            'exit_code' => $exitCode,
            'command' => $cmd
        ]);
        exit;
    }

    // Save to ../contracts.php
    $contractsFile = __DIR__ . '/../contracts.php';
    if (file_exists($contractsFile)) {
        $existing = require $contractsFile;
        if (!isset($existing['contracts'])) $existing['contracts'] = [];
    } else {
        $existing = ['contracts' => []];
    }

    $existing['contracts'][] = [
        'name'      => $contractPath,
        'address'   => $deployedAddress,
        'deployer'  => $deployerAddress,
        'timestamp' => time()
    ];

    $export = "<?php\nreturn " . var_export($existing, true) . ";\n";
    file_put_contents($contractsFile, $export);

    echo json_encode([
        'status' => 'ok',
        'contract_path' => $contractPath,
        'deployed_address' => $deployedAddress,
        'deployer' => $deployerAddress,
        'exit_code' => $exitCode,
        'output' => $outputLines,
        'saved_to' => '../contracts.php'
    ], JSON_PRETTY_PRINT);

    exit;
}

// -----------------------
// HTML DEPLOY FORM
// -----------------------

$accounts = require __DIR__ . '/../accounts.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Deploy Contract</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h2>Deploy Smart Contract</h2>

    <form method="POST" class="mt-3">

        <div class="mb-3">
            <label for="account" class="form-label"><b>Select Account</b></label>
            <select name="account" id="account" class="form-select" required>
                <?php foreach ($accounts['accounts'] as $acc): ?>
                    <option value="<?= htmlspecialchars($acc['private_key']) ?>">
                        <?= htmlspecialchars($acc['index'] . ' — ' . $acc['address']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="contract_path" class="form-label"><b>Contract Path</b> (e.g. src/Wallet.sol:Wallet)</label>
            <input type="text" name="contract_path" id="contract_path"
                   class="form-control" placeholder="src/Wallet.sol:Wallet" required value="src/Wallet.sol:Wallet">
        </div>

        <button type="submit" class="btn btn-primary">Deploy Contract</button>
    </form>
</div>
</body>
</html>
