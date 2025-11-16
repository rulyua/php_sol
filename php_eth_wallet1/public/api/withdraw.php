<?php
$config = require "../../config.php";
require "../../src/Wallet.php";

$amount = floatval($_GET["amount"] ?? 0);
$w = new Wallet($config);
echo json_encode($w->withdraw($amount));
