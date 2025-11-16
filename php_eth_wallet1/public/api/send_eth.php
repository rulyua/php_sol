<?php
$config = require "../../config.php";
require "../../src/Wallet.php";

$amount = floatval($_GET["amount"] ?? 0);
//$amount = 1000000000000000000;
$w = new Wallet($config);
echo json_encode($w->sendEth($amount));
