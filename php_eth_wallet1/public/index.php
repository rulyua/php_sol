<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ethereum Wallet Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">

    <h1 class="text-center mb-4">🦊 Local Ethereum Wallet</h1>

    <div class="card shadow-lg">
        <div class="card-body">

            <h4>Contract Balance:</h4>
            <h2 id="balance" class="text-primary fw-bold">Loading...</h2>

            <hr>

            <div class="row mt-4">
                <div class="col-md-6">
                    <label class="form-label">Send ETH to Contract</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" id="sendAmount" class="form-control" placeholder="0.5 ETH">
                        <button class="btn btn-success" id="sendBtn">Send</button>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Withdraw ETH</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" id="withdrawAmount" class="form-control" placeholder="0.5 ETH">
                        <button class="btn btn-danger" id="withdrawBtn">Withdraw</button>
                    </div>
                </div>
            </div>

            <div id="status" class="mt-4 alert d-none"></div>
        </div>
    </div>

</div>

<!-- jQuery + Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
function refreshBalance() {
    $.get("api/get_balance.php", function(res) {
        try {
            const data = JSON.parse(res);
            $("#balance").text(data.balance + " ETH");
        } catch {
            $("#balance").text("Error retrieving balance");
        }
    });
}

function notify(msg, type) {
    $("#status")
        .removeClass("d-none alert-success alert-danger alert-warning")
        .addClass("alert-" + type)
        .text(msg);
}

$("#sendBtn").click(function() {
    const amount = $("#sendAmount").val();
    if (!amount || amount <= 0) return notify("Enter a valid amount!", "warning");

    notify("Sending transaction...", "warning");

    $.get("api/send_eth.php?amount=" + amount, function(res) {
        notify("Transaction sent: " + res, "success");
        setTimeout(refreshBalance, 1500);
    });
});

$("#withdrawBtn").click(function() {
    const amount = $("#withdrawAmount").val();
    if (!amount || amount <= 0) return notify("Enter a valid amount!", "warning");

    notify("Processing withdrawal...", "warning");

    $.get("api/withdraw.php?amount=" + amount, function(res) {
        notify("Withdrawal submitted: " + res, "success");
        setTimeout(refreshBalance, 1500);
    });
});

// Load initial balance
refreshBalance();
</script>
</body>
</html>

