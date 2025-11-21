<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Contract Control (cast proxy)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<?php
// Load accounts + deployed contracts
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
        <option value="<?= htmlspecialchars($acc['private_key']) ?>">
          <?= htmlspecialchars($acc['index'] . '  ' . $acc['address']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>


  <!-- CONTRACT DROPDOWN -->
  <div class="mb-3">
    <label class="form-label"><b>Select Deployed Contract</b></label>
    <select id="contractSelect" class="form-select">
      <?php if (!empty($contracts['contracts'])): ?>
          <?php foreach ($contracts['contracts'] as $con): ?>
            <option value="<?= htmlspecialchars($con['address']) ?>">
              <?= htmlspecialchars($con['name'] . '  ' . $con['address']) ?>
            </option>
          <?php endforeach; ?>
      <?php else: ?>
          <option disabled>No deployed contracts yet</option>
      <?php endif; ?>
    </select>
  </div>


  <div class="mb-3">
    <button id="send" class="btn btn-primary">Send 1 ETH to contract</button>
    <button id="withdraw" class="btn btn-warning">Withdraw 1 ETH</button>
    <button id="balance" class="btn btn-success">Check Balance</button>
  </div>

  <pre id="output" class="mt-3 bg-dark text-light p-3 rounded"></pre>
</div>


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>

function call(action) {
  let params = {
    action: action,
    contract: $('#contractSelect').val(),
    account: $('#accountSelect').val()
  };

  $.get('api.php', params, function(res){
    try {
      var obj = (typeof res === 'string') ? JSON.parse(res) : res;
      $('#output').text(JSON.stringify(obj, null, 2));
    } catch (e) {
      $('#output').text(String(res));
    }
  }).fail(function(xhr){
    $('#output').text('Request failed: ' + xhr.responseText);
  });
}

$('#send').click(() => call('sendEther'));
$('#withdraw').click(() => call('withdraw'));
$('#balance').click(() => call('balance'));

</script>
</body>
</html>
