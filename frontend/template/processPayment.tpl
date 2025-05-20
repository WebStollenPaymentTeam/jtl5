<!DOCTYPE html>
<html lang="de" itemscope itemtype="https://schema.org/WebPage">
<head>
    <title itemprop="name">{$meta_title}</title>
    <meta http-equiv="content-type" content="text/html; charset={$smarty.const.JTL_CHARSET}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="{$shopURL}/favicon.ico" sizes="48x48" >
    <link rel="icon" href="{$shopURL}/favicon.svg" sizes="any" type="image/svg+xml">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>
<body>
<div id="content">
    <div id="loading-screen">
        <div id="paymentSpinner" class="spinner"></div>
        <div id="paymentCheckmark" class="checkmark" style="display: none"></div>
    </div>
    <div>
        <h2>{$paymentProcessPendingHeading}</h2>
        <p id="paymentPending">{$paymentProcessPendingText}</p>
        <p id="paymentProcessed" style="display: none;">{$paymentProcessFinishedText}</p>
    </div>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const hash = urlParams.get('hash');
        const startTime = Date.now();

        function updateContent() {
            document.getElementById('paymentPending').style.display = 'none';
            document.getElementById('paymentProcessed').style.display = 'block';
            document.getElementById('paymentSpinner').style.display = 'none';
            document.getElementById('paymentCheckmark').style.display = 'block';
        }

        if (!hash) {
            console.error("Required parameter 'paramName' is missing in the URL.");
            window.location = '{$errorURL}?fillOut=-1&mollie_payment_not_completed=1';
        } else {
            const url = '{$shopURL}' + '/ws5_mollie/checkPaymentStatus?hash=' + encodeURIComponent(hash);
            function checkPaymentProcess() {
                fetch(url)
                    .then(response => {
                        if (response.status === 200) {
                            window.location = '{$redirectURL}' + '?mollie_payment_finalized=1&hash=_' + hash;
                        } else if (response.status === 202) {
                            if (Date.now() - startTime >= 10000) {
                                updateContent();
                                setTimeout(checkPaymentProcess, 5000);
                            } else {
                                setTimeout(checkPaymentProcess, 1000);
                            }
                        } else if (response.status === 422) {
                            window.location = '{$redirectURL}' + '?mollie_payment_error=1&hash=_' + hash;
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        if (Date.now() - startTime < 10000) {
                            setTimeout(checkPaymentProcess, 1000);
                        } else {
                            window.location = '{$redirectURL}' + '?mollie_payment_error=1&hash=_' + hash;
                        }
                    });
            }
            checkPaymentProcess();
        }

    </script>
    <style>
        body {
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
        }

        #content {
            text-align: center;
            font-size: 1.5rem;
            padding: 20px;
        }

        #loading-screen {
            margin: 20px;
            display: flex;
            justify-content: center;
        }

        .spinner {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1.5s linear infinite;
        }

        .checkmark {
            display: inline-block;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #28a745;
            position: relative;
        }

        .checkmark::before {
            content: '';
            position: absolute;
            top: 13px;
            left: 19px;
            width: 7px;
            height: 15px;
            border: solid white;
            border-width: 0 5px 5px 0;
            transform: rotate(45deg);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</div>
</body>
</html>