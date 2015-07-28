<?php

require '../../../dbconnect.php';
require '../../../includes/functions.php';
require '../../../includes/gatewayfunctions.php';
require '../../../includes/invoicefunctions.php';
require '../vendor/webtopay/WebToPay.php';

$gatewaymodule = 'paysera';
$gateway = getGatewayVariables($gatewaymodule);

function _log($msg, $data = null) {
    global $gateway;
    logTransaction($gateway['name'], var_export($data, true), $msg);
}

if (!$gateway['type']) {
    echo 'Module Not Activated';
    exit;
}

// Workaround to a bug on some PHP runtimes, when `data` parameter gets
// missing from $_GET due to its length
if (!isset($_GET['data'])) {
    $query = htmlspecialchars_decode($_SERVER['QUERY_STRING']);
    parse_str($query, $_GET);
}

try {

    $res = WebToPay::checkResponse($_GET, array(
        'projectid' => $gateway['projectID'],
        'sign_password' => $gateway['projectPass'],
    ));

    $orderid = intval(mysql_real_escape_string($res['orderid']));
    $invoiceid = checkCbInvoiceID($orderid, $gateway['paysera']);
    $transid = $res['requestid'];
    $amount = $res['amount'] / 100;

    // Check if we want to redirect user to the invoice
    if (isset($_REQUEST['accepturl'])) {
        if (!empty($gateway['systemurl'])) {
            header('Location: ' . $gateway['systemurl'] .
                '/viewinvoice.php?id=' . $invoiceid);
        } else {
            header('Location: /viewinvoice.php?id=' . $invoiceid);
        }
        exit;
    }

    // Convert currency
    if (isset($gateway['convertto'])) {
        $result = mysql_query("
            SELECT rate FROM tblcurrencies
            WHERE code = '" . mysql_real_escape_string($res['currency']) . "'
        ");
        $rate = '';
        while ($row = mysql_fetch_array($result)) {
            $rate = $row['rate'];
        }
        $amount *= $rate;
    }

    // Formats amount in cent units to string with dot separator
    $amount = number_format($amount, 2, '.', '');

    // Catch processing status
    if ($res['status'] == 2) {
        _log('[Fail] Paid, but still processing', $res);
        exit;
    }

    // Catch all unpaid statuses
    if ($res['status'] != 1) {
        _log('[Fail] Unpaid', $res);
        exit;
    }

    // Catch zero or negative amount
    if ($amount <= 0) {
        _log('[Fail] Paid zero amount', $res);
        exit;
    }

    // Register payment
    checkCbTransId($transid); // If finds a similar transaction, force quits
    addInvoicePayment($invoiceid, $transid, $amount, 0, $gatewaymodule);
    _log('[Success] Paid', array(
        'amount' => $amount,
        'response' => $res,
    ));

    echo 'OK';

} catch (Exception $e) {
    _log('[Error] Exception', array(
        'exception' => get_class($e) . ": " . $e->getMessage(),
        'request' => $_REQUEST,
    ));
    exit;
}
