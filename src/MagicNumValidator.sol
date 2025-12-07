// SPDX-License-Identifier: MIT
pragma solidity ^0.8.13;

import "forge-std/console.sol";

interface IMagicNum {
    function solver() external view returns (address);
}

interface ISolver {
    function whatIsTheMeaningOfLife() external view returns (bytes32);
}

contract MagicNumValidator {

    IMagicNum public target;

    constructor(address _target) {
        target = IMagicNum(_target);
    }

    function validate() public view returns (bool) {

        // Get solver contract address
        address solver = target.solver();
        if (solver == address(0)) return false;

        // Call solver
        bytes32 result = ISolver(solver).whatIsTheMeaningOfLife();

        // Convert result to uint for printing (no bytes32 in log)
        uint256 resultAsUint = uint256(result);

        console.log("result (uint256) =", resultAsUint == 42);
//        console.log("expected =", 42);

        // If you still want to see full bytes:
        // console.logBytes32(result);

        return resultAsUint == 42;
    }
}
