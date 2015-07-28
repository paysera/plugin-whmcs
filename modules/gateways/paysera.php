<?php

require_once 'vendor/webtopay/WebToPay.php';

function paysera_config() {
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Paysera.com'
        ),
        'projectID' => array(
            'FriendlyName' => 'Project ID',
            'Type' => 'text',
            'Size' => '10',
            'Description' => 'Enter unique Project ID',
        ),
        'projectPass' => array(
            'FriendlyName' => 'Project password',
            'Type' => 'text',
            'Size' => '32',
            'Description' => 'Enter unique sign password',
        ),
        'testmode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick this to enable test mode'
        ),
    );
}

function paysera_link($params) {

    if (!$params['clientdetails']['email']) {
        return;
    }

    try {

        $request = WebToPay::buildRequest(array(
            'projectid'     => $params['projectID'],
            'sign_password' => $params['projectPass'],
            'orderid'       => $params['invoiceid'],
            'amount'        => intval(number_format($params['amount'], 2, '', '')),
            'currency'      => $params['currency'],
            'accepturl'     => $params['systemurl'] . '/modules/gateways/callback/paysera.php?accepturl=1',
            'cancelurl'     => $params['systemurl'] . '/clientarea.php',
            'callbackurl'   => $params['systemurl'] . '/modules/gateways/callback/paysera.php',
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

        $code  = '<form method="get" action="' . WebToPay::getPaymentUrl() . '"">';
        $code .= '<input type="hidden" name="data" value="' . $request["data"] . '">';
        $code .= '<input type="hidden" name="sign" value="' . $request["sign"] . '">';
        $code .= '<input type="submit" value="Pay now">';
        $code .= '</form>';

        return $code;

    } catch (WebToPayException $e) {
        echo get_class($e) . ': ' . $e->getMessage();
    }

}
