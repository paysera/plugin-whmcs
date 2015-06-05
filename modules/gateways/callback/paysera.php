<?php

include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

require_once "../vendor/webtopay/WebToPay.php";

$gatewaymodule = "paysera";
$GATEWAY = getGatewayVariables( $gatewaymodule );

if ( ! $GATEWAY["type"] ) die("Module Not Activated");

function _log( $msg, $data = null ) {
    logTransaction( $GATEWAY["name"], var_export( $data, true ), $msg );
}

try {

    $response = WebToPay::validateAndParseData( $_REQUEST, $GATEWAY['projectID'], $GATEWAY['projectPass'] );
    $orderid = intval( mysql_real_escape_string( $response['orderid'] ) );
    $invoiceid = checkCbInvoiceID( $orderid, $GATEWAY["paysera"] );
    $transid = $response["requestid"];
    $amount = $response["amount"] / 100;

    // Check if we want to redirect user to the invoice
    if ( isset( $_REQUEST['accepturl'] ) ) {
        header( "Location: /viewinvoice.php?id=" . $invoiceid );
        exit;
    }

    // Convert currency
    if ( isset( $GATEWAY['convertto'] ) ) {
        $result = mysql_query("SELECT rate FROM tblcurrencies WHERE code = '" . $response['currency'] . "'");
        while ( $row = mysql_fetch_array($result) ) $rate = $row['rate'];
        $amount *= $rate;
    }

    $amount = number_format( $amount, 2, ".", "" );

    // Check status is something other, than unpaid (0)
    if ( $response["status"] > 0 ) {
        if ( $amount <= 0 ) {
            _log( "[Fail] Paid zero amount", $response );
            exit;
        }
        if ( $response["status"] == 2 ) {
            _log( "[Fail] Paid, but still processing", $response );
            exit;
        }
        checkCbTransId( $transid ); // Check transid isn't in database, otherwise exit
        addInvoicePayment( $invoiceid, $transid, $amount, 0, $gatewaymodule );
        _log( "[Success] Paid", array( "amount" => $amount, "response" => $response ) );
        exit("OK");
    }

    // Log failure if everything else fails
    _log( "[Fail] Unpaid", $response );
    exit;

} catch ( Exception $e ) {
    _log( "[Error] Exception", array(
        "exception" => get_class($e) . ": " . $e->getMessage(),
        "request" => $_REQUEST
    ));
    exit;
}
