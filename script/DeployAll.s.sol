// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import {Script, console2} from "forge-std/Script.sol";
import {PoolToken, Pool, BetHouse} from "../src/BetHouse.sol";

contract DeployAll is Script {
    function run() external {
        vm.startBroadcast();

        // 1) Deploy Wrapped Token (WRAP)
        PoolToken wrappedToken = new PoolToken("Wrapped Token", "WRAP");
        console2.log("Wrapped Token deployed at:", address(wrappedToken));

        // 2) Deploy Deposit Token (PDT)
        PoolToken depositToken = new PoolToken("Deposit Token", "PDT");
        console2.log("Deposit Token deployed at:", address(depositToken));

        // 3) Deploy Pool
        Pool pool = new Pool(address(wrappedToken), address(depositToken));
        console2.log("Pool deployed at:", address(pool));

        // 4) Give Pool permission to mint/burn WRAP
        wrappedToken.transferOwnership(address(pool));
        console2.log("WRAP owner transferred to Pool");

        // 5) Deploy BetHouse
        BetHouse betHouse = new BetHouse(address(pool));
        console2.log("BetHouse deployed at:", address(betHouse));

        console2.log("=====================================");
        console2.log("Deployment complete");
        console2.log("WRAP:", address(wrappedToken));
        console2.log("PDT:", address(depositToken));
        console2.log("POOL:", address(pool));
        console2.log("BET_HOUSE:", address(betHouse));
        console2.log("=====================================");

        vm.stopBroadcast();
    }
}
