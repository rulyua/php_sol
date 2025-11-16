<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Auto Deploy Wallet</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
  <h2>Auto-Deploy Wallet</h2>
  <div class="mb-3">
    <button id="deploy" class="btn btn-danger">🚀 Deploy Contract</button>
    <button id="send" class="btn btn-primary contract-action">Send 1 ETH</button>
    <button id="withdraw" class="btn btn-warning contract-action">Withdraw 1 ETH</button>
    <button id="balance" class="btn btn-success contract-action">Check Balance</button>
  </div>
  <pre id="output" class="mt-3 bg-dark text-light p-3 rounded"></pre>
  <div class="mt-3"><small>Make sure <code>forge</code> and <code>cast</code> are in PATH and Anvil is running.</small></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function call(action, params) {
  params = params || {};
  params.action = action;
  $.get('api.php', params, function(res){
    var obj = (typeof res === 'string') ? JSON.parse(res) : res;
    $('#output').text(JSON.stringify(obj, null, 2));
    if (obj.status === 'success' && obj.address) {
      $('.contract-action').prop('disabled', false);
    }
  }).fail(function(xhr){
    $('#output').text('Request failed: ' + xhr.responseText);
  });
}

$('#deploy').click(function(){ call('deploy'); });
$('#send').click(function(){ call('sendEther'); });
$('#withdraw').click(function(){ call('withdraw'); });
$('#balance').click(function(){ call('balance'); });

// disable contract buttons until deployed
$('.contract-action').prop('disabled', true);
</script>
</body>
</html>
