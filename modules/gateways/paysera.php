<?php

require_once('vendor/webtopay/libwebtopay/WebToPay.php');

function paysera_config() {
    $configarray = array(
        "FriendlyName"  => array("Type" => "System", "Value" => "Paysera.com / Mokejimai.lt"),
        "adminusername" => array("FriendlyName" => "Admin Username", "Type" => "text", "Size" => "15", "Description" => "Enter admin username"),
        "projectID"     => array("FriendlyName" => "Project ID", "Type" => "text", "Size" => "10", "Description" => "Enter unique Project ID",),
        "projectPass"   => array("FriendlyName" => "Project password", "Type" => "text", "Size" => "32", "Description" => "Enter unique sign password",),
        "testmode"      => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to enable test mode",),
    );
    return $configarray;
}

function paysera_link($params) {

    $URL = array();

    $URL['accept']   = $params['systemurl'] . '/modules/gateways/callback/paysera.php?accepturl=1';
    $URL['cancel']   = $params['systemurl'] . '/clientarea.php';
    $URL['callback'] = $params['systemurl'] . '/modules/gateways/callback/paysera.php';
    try {
        WebToPay::redirectToPayment(array(
            'projectid'     => $params['projectID'],
            'sign_password' => $params['projectPass'],

            'orderid'       => $params['invoiceid'],
            'amount'        => intval(number_format($params['amount'], 2, '', '')),
            'currency'      => $params['currency'],

            'accepturl'     => $URL['accept'],
            'cancelurl'     => $URL['cancel'],
            'callbackurl'   => $URL['callback'],

            'p_firstname'   => $params['clientdetails']['firstname'],
            'p_lastname'    => $params['clientdetails']['lastname'],
            'p_email'       => $params['clientdetails']['email'],
            'p_street'      => $params['clientdetails']['address1'] . $params['clientdetails']['address2'],
            'p_city'        => $params['clientdetails']['city'],
            'p_state'       => $params['clientdetails']['state'],
            'p_zip'         => $params['clientdetails']['postcode'],
            'p_countrycode' => $params['clientdetails']['country'],
            'test'          => $params['testmode'] == 'on' ? 1 : 0,
        ));
    } catch (WebToPayException $e) {
        echo get_class($e) . ': ' . $e->getMessage();
    }
}
