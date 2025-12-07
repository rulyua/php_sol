<?php 
include "prepare.php";
if (!$_POST):
?>
<pre>

1. MagicNum.sol
(The vulnerable challenge contract)
pragma solidity ^0.8.0;

contract MagicNum {
    address public solver;

    function setSolver(address _solver) public {
        solver = _solver;
    }
}

❗ Purpose

This mimics the Ethernaut challenge:

The contract allows anyone to set the solver address

The challenge is to deploy a minimal contract that returns 42

And then call setSolver(address) to register it

🔍 Key field

address public solver — stores the address of the solver contract

🔧 Key function

setSolver(address _solver) — sets the solver address

This contract does not check anything, it only stores the address.

Your exploit provides the correct solver, and then the validator checks it.

🟩 2. MagicNumValidator.sol
(Contract that verifies whether the solver is correct)
bytes32 result = ISolver(solver).whatIsTheMeaningOfLife();
uint256 resultAsUint = uint256(result);
return resultAsUint == 42;

What it does:

Reads the solver address from MagicNum:

address solver = target.solver();


Calls the solver function inside that solver contract:

bytes32 result = ISolver(solver).whatIsTheMeaningOfLife();


Converts result from bytes32 → uint256
Solidity returns everything as bytes32, even if it's just a number.

Logs whether result equals 42

console.log("result (uint256) =", resultAsUint == 42);


Returns true if correct

return resultAsUint == 42;

🧠 Why use bytes32?

All EVM function return values are 32 bytes.
Even if the result is just 42, the EVM returns:

0x000000000000000000000000000000000000000000000000000000000000002a


So conversion is needed:

uint256(result) → 42

🟦 3. The PHP Exploit Script
(Handles auto-deploy, verification and validation)

Your PHP script controls the entire workflow:

✔ Deploys solver bytecode

Using:

$initcode_hex = "600a600c600039600a6000f3602a60005260206000f3"; // or 2b for 43


This deploys the minimal contract:

return 42;


or

return 43;


via:

cast send --create ...

✔ Verifies the runtime matches expected code
0x602a60005260206000f3   // return 42

✔ Calls MagicNum.setSolver()

Stores your solver address inside MagicNum.

✔ Deploys MagicNumValidator.sol

Runs:

forge create ...

✔ Calls validator and prints the result
cast call validator validate()


Your improved PHP code properly interprets:

0x000...001 → TRUE

0x000...000 → FALSE

so the output becomes:

VALIDATOR RESULT: SUCCESS ✔


or

VALIDATOR RESULT: FAIL ✘
<?php
exit;
endif;
// ========================================================================
// Helper: clean minimal output
// ========================================================================
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
function cleanCastMinimal($txt) {
    $result = [];

    // contract address
    if (preg_match('/contractAddress\s*:?\s*(0x[a-fA-F0-9]{40})/i', $txt, $m)) {
        $result[] = "contractAddr:  " . $m[1];
    }

    // status
    if (preg_match('/status\s*:?\s*([0-9]+|\w+)/i', $txt, $m)) {
        $status = ($m[1] == "1" || strtolower($m[1]) == "success") ? "success" : "failed";
        $result[] = "status:        " . $status;
    }

    return implode("\n", $result) . "\n";
}

// ========================================================================
// HEADER
// ========================================================================
echo "<pre>";
echo "<div style='color:red; font-size:24px; font-weight:bold;'>MAGICNUMBER EXPLOIT</div>\n";
echo "Target MagicNum: $CONTRACT\n";
echo "Player: $PLAYER\n\n";

// ========================================================================
// Solver BEFORE
// ========================================================================
echo "Solver Before\n";
echo cast("cast call $CONTRACT \"solver()\" --rpc-url $RPC");
echo "\n";

// ========================================================================
// DEPLOY SOLVER (BASE64 METHOD)
// ========================================================================
echo "=== DEPLOYING SOLVER (Base64 raw bytes) ===\n";

$initcode_hex = "600a600c600039600a6000f3602a60005260206000f3";//42
//$initcode_hex = "600a600c600039600a6000f3602b60005260206000f3";//43
$raw_bytes    = hex2bin($initcode_hex);
$base64       = base64_encode($raw_bytes);

echo "Base64 payload: $base64\n\n";

$deployCmd = sprintf(
    'cast send --private-key %s --rpc-url %s --create $(printf "%%s" "%s" | base64 -d | xxd -p | tr -d "\\n") 2>&1',
    escapeshellarg($PK),
    escapeshellarg($RPC),
    $base64
);

$deployOut = shell_exec($deployCmd);

// print compressed output
echo cleanCastMinimal($deployOut);

// extract solver address
if (preg_match('/contractAddress\s*:?\s*(0x[a-fA-F0-9]{40})/i', $deployOut, $m)) {
    $solver = $m[1];
} elseif (preg_match('/(0x[a-fA-F0-9]{40})/', $deployOut, $m)) {
    $solver = $m[1];
} else {
    echo "ERROR: Could not extract solver address\n";
    exit("</pre>");
}

echo "Deployed Solver Address: $solver\n\n";

// ========================================================================
// VERIFY SOLVER RUNTIME
// ========================================================================
echo "=== VERIFY SOLVER RUNTIME ===\n";
$runtime = trim(cast("cast code $solver --rpc-url $RPC"));
echo "Runtime: $runtime\n";

$expectedRuntime = "0x602a60005260206000f3";

if ($runtime === "0x" || empty($runtime)) {
    echo "Runtime code is EMPTY — deployment failed!\n";
    exit("</pre>");
}

if (strtolower($runtime) !== strtolower($expectedRuntime)) {
    echo "Runtime DOES NOT MATCH expected MagicNumber solver\n";
    echo "Expected: $expectedRuntime\n";
    echo "Got:      $runtime\n";
//    exit("</pre>");
}

echo "Runtime OK — correct MagicNumber solver deployed!\n\n";

// ========================================================================
// SET SOLVER
// ========================================================================
echo "=== SET SOLVER ===\n";
$out = cast("cast send $CONTRACT \"setSolver(address)\" $solver --private-key $PK --rpc-url $RPC");
echo cleanCastMinimal($out) . "\n";

// ========================================================================
// Solver AFTER
// ========================================================================
echo "Solver After\n";
echo cast("cast call $CONTRACT \"solver()\" --rpc-url $RPC");
echo "\n";

// ========================================================================
// VALIDATOR DEPLOY
// ========================================================================
echo "=== DEPLOYING VALIDATOR (MagicNumValidator) ===\n";

$cmd = "forge create --json --broadcast src/MagicNumValidator.sol:MagicNumValidator --private-key $PK --constructor-args $CONTRACT --rpc-url $RPC";
$out = cast($cmd);

echo cleanCastMinimal($out);

if (!preg_match('/"deployedTo"\s*:\s*"(0x[a-fA-F0-9]{40})"/', $out, $vm)) {
    echo "ERROR: Could not deploy validator\n$out\n";
    exit("</pre>");
}
$validator = $vm[1];

echo "Deployed Validator at: $validator\n\n";
// ========================================================================
// VALIDATE()
// ========================================================================
echo "=== VALIDATE() ===\n";

$validateOut = trim(shell_exec(
    "cast call $validator \"validate()\" --rpc-url $RPC 2>&1"
));

// show raw output
echo "> cast call $validator validate()\n";
echo "raw: $validateOut\n\n";

// decode result
$validateClean = strtolower($validateOut);

// cast call returns:
// 0x01…01 → true
// 0x00…00 → false

if ($validateClean === "0x" || $validateClean === "") {
    echo "VALIDATOR ERROR: no return value\n";
} elseif (preg_match('/0x0{0,63}1$/', $validateClean)) {
    echo "VALIDATOR RESULT: SUCCESS ✔ (solver is correct)\n";
} else {
    echo "VALIDATOR RESULT: FAIL ✘ (wrong solver)\n";
}



echo "</pre>";
?>

