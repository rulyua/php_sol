<?php
// ======================================================
// 1. HANDLE REMOVAL REQUEST
// ======================================================
if (isset($_POST['remove_contract'])) {

    $removeAddress = $_POST['remove_contract'];

    $contractsFile = __DIR__ . '/../contracts.php';
    if (file_exists($contractsFile)) {
        $existing = require $contractsFile;
    } else {
        $existing = ['contracts' => []];
    }

    // remove selected contract
    $existing['contracts'] = array_values(array_filter(
        $existing['contracts'],
        fn($c) => $c['address'] !== $removeAddress
    ));

    // save
    $export = "<?php\nreturn " . var_export($existing, true) . ";\n";
    file_put_contents($contractsFile, $export);

    header("Location: deploy_api.php");
    exit;
}



// ======================================================
// 2. HANDLE DEPLOY REQUEST
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['contract_path'])
    && !isset($_POST['remove_contract'])) 
{
    header('Content-Type: application/json');

    $accounts = require __DIR__ . '/../accounts.php';

    $privateKey   = $_POST['account'] ?? null;
    $contractPath = $_POST['contract_path'] ?? null;

    if (!$privateKey) {
        echo json_encode(['error' => 'Missing selected account']);
        exit;
    }
    if (!$contractPath) {
        echo json_encode(['error' => 'Missing contract_path']);
        exit;
    }

    // Find deployer info
    $deployerAddress = null;
    $deployerName    = null;

    foreach ($accounts['accounts'] as $acc) {
        if ($acc['private_key'] === $privateKey) {
            $deployerAddress = $acc['address'];
            $deployerName    = $acc['index'];
            break;
        }
    }

    if (!$deployerAddress) {
        echo json_encode(['error' => 'Private key not found']);
        exit;
    }

    $rpcUrl = 'http://127.0.0.1:8545';

    // ====================================================
    // AUTO-CONSTRUCTOR SUPPORT (Token.sol)
    // ====================================================
    $constructorArgs = "";

    if (strpos($contractPath, "Token.sol:Token") !== false) {
        // default token supply
        $constructorArgs = " --constructor-args 10000000000000000000";
    }

    // Build command
    $cmd = sprintf(
        "forge create --json --broadcast --rpc-url %s --private-key %s %s%s 2>&1",
        escapeshellarg($rpcUrl),
        escapeshellarg($privateKey),
        escapeshellarg($contractPath),
        $constructorArgs
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

    // Save contract
    $contractsFile = __DIR__ . '/../contracts.php';
    if (file_exists($contractsFile)) {
        $existing = require $contractsFile;
        if (!isset($existing['contracts'])) $existing['contracts'] = [];
    } else {
        $existing = ['contracts' => []];
    }

    $existing['contracts'][] = [
        'name'          => $contractPath,
        'address'       => $deployedAddress,
        'deployer'      => $deployerAddress,
        'deployer_name' => $deployerName,
        'timestamp'     => time()
    ];

    $export = "<?php\nreturn " . var_export($existing, true) . ";\n";
    file_put_contents($contractsFile, $export);

    echo json_encode([
        'status'        => 'ok',
        'contract_path' => $contractPath,
        'deployed_address' => $deployedAddress,
        'deployer'      => $deployerAddress,
        'deployer_name' => $deployerName,
        'exit_code'     => $exitCode,
        'output'        => $outputLines
    ], JSON_PRETTY_PRINT);

    exit;
}



// ======================================================
// 3. HTML VIEW
// ======================================================
$accounts = require __DIR__ . '/../accounts.php';

// Contract list from /src
$srcDir = realpath(__DIR__ . '/../../src');
$contractOptions = [];

foreach (glob($srcDir . '/*.sol') as $file) {
    $filename = basename($file);
    $contractName = pathinfo($filename, PATHINFO_FILENAME);
    $contractOptions[] = "src/" . $filename . ":" . $contractName;
}

// load deployed
$contractsFile = __DIR__ . '/../contracts.php';
$deployed = file_exists($contractsFile) ? require $contractsFile : ['contracts' => []];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Deploy Contract</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<style>
body { font-size: 15px; }

.table td, .table th {
    padding: 4px !important;
    vertical-align: middle !important;
}

.table td.name-col,
.table th.name-col {
    white-space: nowrap;
    max-width: 350px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.delete-cell {
    text-align: center;
    vertical-align: middle !important;
}
</style>

</head>
<body class="p-4">

<div class="container">
    <h2>Deploy Smart Contract</h2>

    <!-- DEPLOY FORM -->
    <form method="POST" class="mt-3 mb-5">

        <div class="mb-3">
            <label class="form-label"><b>Select Account</b></label>
            <select name="account" class="form-select" required>
                <?php foreach ($accounts['accounts'] as $acc): ?>
                    <option value="<?= htmlspecialchars($acc['private_key']) ?>">
                        <?= htmlspecialchars($acc['index']) ?> — <?= htmlspecialchars($acc['address']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label"><b>Select Contract</b></label>
            <select name="contract_path" class="form-select" required>
                <option value="">-- select contract from /src --</option>
                <?php foreach ($contractOptions as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Deploy Contract</button>

    </form>


    <!-- DEPLOYED CONTRACTS TABLE -->
    <h3>Deployed Contracts</h3>

    <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
        <tr>
            <th class="name-col">Name</th>
            <th>Deployer</th>
            <th>Address</th>
            <th>Time</th>
            <th>Remove</th>
        </tr>
        </thead>

        <tbody>
        <?php if (empty($deployed['contracts'])): ?>
            <tr><td colspan="5" class="text-center">No contracts deployed yet.</td></tr>
        <?php else: ?>
            <?php foreach ($deployed['contracts'] as $c): ?>
                <tr>
                    <td class="name-col">
                        <small class="text-muted">
                            <?= htmlspecialchars($c['name']) ?>
                            <b>(<?= htmlspecialchars($c['deployer_name'] ?? 'Unknown') ?>)</b>
                        </small>
                    </td>

                    <td>
                        <small class="text-muted"><?= htmlspecialchars($c['deployer']) ?></small>
                    </td>

                    <td>
                        <small class="text-muted"><?= htmlspecialchars($c['address']) ?></small>
                    </td>

                    <td><?= date("Y-m-d H:i:s", $c['timestamp']) ?></td>

                    <td class="delete-cell">
                        <form method="POST" onsubmit="return confirm('Remove this contract?');">
                            <input type="hidden" name="remove_contract" value="<?= htmlspecialchars($c['address']) ?>">
                            <button class="btn btn-danger btn-sm">X</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>

    </table>

</div>
</body>
</html>
