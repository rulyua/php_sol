<?php
$config = require "../../config.php";
require "../../src/Wallet.php";

$w = new Wallet($config);
echo json_encode(["balance" => $w->getContractBalance()]);
