<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Contract Control (cast proxy)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<div class="container">
  <h2>Smart Contract Controls (cast proxy)</h2>

  <div class="mb-3">
    <button id="send" class="btn btn-primary">Send 1 ETH to contract</button>
    <button id="withdraw" class="btn btn-warning">Withdraw 1 ETH</button>
    <button id="balance" class="btn btn-success">Check Balance</button>
  </div>

  <pre id="output" class="mt-3 bg-dark text-light p-3 rounded"></pre>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function call(action, params) {
  params = params || {};
  params.action = action;
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

$('#send').click(function(){ call('sendEther'); });
$('#withdraw').click(function(){ call('withdraw'); });
$('#balance').click(function(){ call('balance'); });
</script>
</body>
</html>
