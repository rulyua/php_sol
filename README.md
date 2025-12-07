# php_sol — Full Featured PHP + Foundry Smart‑Contract Toolkit

`php_sol` is a complete development environment that lets you **deploy**, **call**, and **interact** with Solidity smart contracts directly from **PHP**, without needing Node.js, Hardhat, or web3.js.  
It is designed for developers who want a **simple, scriptable, server‑friendly** interface to Foundry (`forge`, `cast`) while keeping powerful dashboards in PHP.

This repository includes:

- ✔ A full **contract deployment pipeline** (Foundry scripts)
- ✔ PHP tools to **send transactions**, **read state**, and **build dashboards**
- ✔ Automatic **contract discovery**, **address management**, and **multi‑account support**

---

## 🚀 Features

### 🔹 1. PHP as a Blockchain Control Panel  
Interact with blockchain contracts directly via PHP:

- Deploy contracts  
- Call functions  
- Send transactions  
- Sign operations using private keys  
- Decode data and show dashboards in HTML  

### 🔹 2. Fully Scriptable with Foundry  
The repository uses:

- `forge script` — deployment automation  
- `cast send` — signing transactions  
- `cast call` — querying chain state  


#### **MagicNumber exploit panel**  
- Deploy target  
- Deploy solver  
- Verify exploit result  
- Automatic contract detection  

---

# 📦 Installation

### 1. Clone the repository
```bash
git clone https://github.com/rulyua/php_sol.git
cd php_sol
```

### 2. Install Foundry
```bash
curl -L https://foundry.paradigm.xyz | bash
foundryup
```

### 3. Start local blockchain (Anvil)
```bash
anvil --steps-tracing
```

### 4. Install dependencies
```bash
forge install OpenZeppelin/openzeppelin-contracts
```

### 5. Copy environment configuration
```
cp .env.example .env
```

Edit your private keys, RPC URL, and account labels.


# ⚙ Configuration

### accounts.php
```php
return [
    "accounts" => [
        [
            "index" => 0,
            "address" => "0x123...",
            "private_key" => "0xabc..."
        ],
        ...
    ]
];
```

### .env file
```
RPC_URL=http://127.0.0.1:8545
ACCOUNT_1_PRIVATE_KEY=0x...
ACCOUNT_1_LABEL=Main Wallet
```

