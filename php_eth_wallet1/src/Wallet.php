<?php

class Wallet {
    private $rpc;
    private $privateKey;
    private $contract;

    public function __construct($config) {
        $this->rpc = $config["rpc"];
        $this->privateKey = $config["private_key"];
        $this->contract = $config["contract_address"];
    }

    private function rpc($method, $params = []) {
        $payload = json_encode([
            "jsonrpc" => "2.0",
            "id" => 1,
            "method" => $method,
            "params" => $params,
        ]);

        $ch = curl_init($this->rpc);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function getContractBalance() {
        $selector = "0x" . substr(sha1("balance()"), 0, 8);

        $response = $this->rpc("eth_call", [[
            "to" => $this->contract,
            "data" => $selector
        ], "latest"]);

        return hexdec($response["result"]) / 1e18;
    }

    public function sendEth($amount) {
        $value = "0x" . dechex($amount * 1e18);

        return $this->rpc("eth_sendTransaction", [[
            "from" => "0x0000000000000000000000000000000000000000",
            "to"   => $this->contract,
            "value" => $value
        ]]);
    }

    public function withdraw($amount) {
        $selector = "0x" . substr(sha1("withdraw(uint256)"), 0, 8);
        $valueHex = str_pad(dechex($amount * 1e18), 64, "0", STR_PAD_LEFT);
        $data = $selector . $valueHex;

        return $this->rpc("eth_sendTransaction", [[
            "from" => "0x0000000000000000000000000000000000000000",
            "to" => $this->contract,
            "data" => "0x" . $data
        ]]);
    }
}
