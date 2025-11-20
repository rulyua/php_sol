<?php

/**
 * parse_anvil_out.php
 *
 * Reads an anvil.out file, extracts the 10 default Anvil accounts + 10 private keys,
 * and writes them into accounts.php as a PHP config array.
 */

$inputFile  = __DIR__ . '/anvil.out';
$outputFile = __DIR__ . '/accounts.php';

if (!file_exists($inputFile)) {
    die("Error: anvil.out not found at $inputFile\n");
}

$content = file_get_contents($inputFile);

// -------------------------
// Extract addresses
// -------------------------
preg_match_all('/\(\d+\)\s+(0x[0-9A-Fa-f]{40})/', $content, $addrMatches);

$addresses = $addrMatches[1] ?? [];

if (count($addresses) < 10) {
    die("Error: Could not extract 10 accounts from anvil.out\n");
}

// -------------------------
// Extract private keys
// NOTE: Your anvil.out has no 'Private key:' labels,
// so we extract the last 10 hex values in the file.
// -------------------------

// Find ALL hex values (addresses + keys)
preg_match_all('/0x[0-9A-Fa-f]+/', $content, $allHex);

// Last 10 entries are private keys
$allHex = $allHex[0];
$privateKeys = array_slice($allHex, -10);

if (count($privateKeys) !== 10) {
    die("Error: Could not extract 10 private keys from anvil.out\n");
}

// -------------------------
// Combine into config array
// -------------------------

$accounts = [];

for ($i = 0; $i < 10; $i++) {
    $accounts[] = [
        'index'       => $i,
        'address'     => $addresses[$i],
        'private_key' => $privateKeys[$i]
    ];
}

// -------------------------
// Generate accounts.php output
// -------------------------

$export = "<?php\n\nreturn [\n    'accounts' => [\n";

foreach ($accounts as $acc) {
    $export .= sprintf(
        "        ['index'=>%d,'address'=>'%s','private_key'=>'%s'],\n",
        $acc['index'],
        $acc['address'],
        $acc['private_key']
    );
}

$export .= "    ]\n];\n";

file_put_contents($outputFile, $export);

echo "Generated accounts.php successfully at: $outputFile\n";
