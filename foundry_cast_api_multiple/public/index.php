<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Contract Control (cast proxy)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<?php
$accounts  = require __DIR__ . '/../accounts.php';
$contracts = require __DIR__ . '/../contracts.php';
?>

<div class="container">
  <h2>Smart Contract Controls (cast proxy)</h2>

  <!-- ACCOUNT DROPDOWN -->
  <div class="mb-3">
    <label class="form-label"><b>Select Account</b></label>
    <select id="accountSelect" class="form-select">
      <?php foreach ($accounts['accounts'] as $acc): ?>
        <option value="<?= htmlspecialchars($acc['address']) ?>"
                data-private="<?= htmlspecialchars($acc['private_key']) ?>">
          <?= htmlspecialchars($acc['index'] . '  ' . $acc['address']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- CONTRACT DROPDOWN -->
  <div class="mb-3">
    <label class="form-label"><b>Select Contract</b></label>
    <select id="contractSelect" class="form-select"></select>
  </div>

  <div class="mb-3">
    <button id="send" class="btn btn-primary">Send 1 ETH to contract</button>
    <button id="withdraw" class="btn btn-warning">Withdraw 1 ETH</button>
    <button id="balance" class="btn btn-success">Contract Balance</button>
		<button id="account_balance" class="btn btn-success">Account Balance</button>
  </div>

  <pre id="output" class="mt-3 bg-dark text-light p-3 rounded"></pre>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
// -------------------------
// LOAD CONTRACTS (from PHP)
// -------------------------
const CONTRACTS = <?= json_encode($contracts['contracts'] ?? []) ?>;

// Group contracts by deployer address
const CONTRACTS_BY_DEPLOYER = {};
for (const c of CONTRACTS) {
    if (!CONTRACTS_BY_DEPLOYER[c.deployer]) {
        CONTRACTS_BY_DEPLOYER[c.deployer] = [];
    }
    CONTRACTS_BY_DEPLOYER[c.deployer].push(c);
}

// -------------------------
// UPDATE CONTRACT DROPDOWN
// -------------------------
function refreshContracts() {
    const accAddress = $('#accountSelect').val();
    const $contractSelect = $('#contractSelect');

    $contractSelect.empty();

    const list = CONTRACTS_BY_DEPLOYER[accAddress] || [];

    if (list.length === 0) {
        $contractSelect.append(
            $('<option disabled selected>').text('No contracts for this account')
        );
        return;
    }

    for (const c of list) {
        $contractSelect.append(
            $('<option>').val(c.address)
                         .text(c.name + '  ' + c.address)
        );
    }
}

// Trigger contract refresh on load and account change
$('#accountSelect').on('change', refreshContracts);
$(document).ready(refreshContracts);


// -------------------------
// CALL BACKEND API
// -------------------------
function call(action) {
	account = $('#accountSelect option:selected').data('private');
  
  if( action == 'account_balance' )
  	account = $('#accountSelect option:selected').val();
  
  let params = {
    action: action,
    account: account,
    contract: $('#contractSelect').val()
  };

  $.get('api.php', params, function(res){

    if (typeof res === 'object') {
        $('#output').text(JSON.stringify(res, null, 2));
        return;
    }

    try {
        const parsed = JSON.parse(res);
        $('#output').text(JSON.stringify(parsed, null, 2));
    } catch {
        $('#output').text(res);
    }
  });
}

$('#send').click(() => call('sendEther'));
$('#withdraw').click(() => call('withdraw'));
$('#balance').click(() => call('balance'));
$('#account_balance').click(function(){ call('account_balance'); });
</script>

</body>
</html>
