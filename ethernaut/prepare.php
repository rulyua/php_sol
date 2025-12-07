<?php
include 'functions.php';

$RPC = "http://127.0.0.1:8545";
$accountsFile  = __DIR__ . "/accounts.php";
$contractsFile = __DIR__ . "/bethouse_contracts.php";

if (!file_exists($accountsFile) || !file_exists($contractsFile)) {
    die("<b style='color:red'>Missing accounts.php or bethouse_contracts.php</b>");
}

$ACC = require $accountsFile;
$C   = require $contractsFile;

$MARKETS = $C["MARKETS"];
