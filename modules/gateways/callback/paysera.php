<?php

include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

require_once('../vendor/webtopay/libwebtopay/WebToPay.php');

$gatewaymodule = "paysera";
$GATEWAY       = getGatewayVariables($gatewaymodule);



if (!$GATEWAY["type"])
    die("Module Not Activated");

try {
    $response = WebToPay::validateAndParseData($_REQUEST, $GATEWAY['projectID'], $GATEWAY['projectPass']);

    $orderid   = intval(mysql_real_escape_string($response['orderid']));
    $invoiceid = checkCbInvoiceID($orderid, $GATEWAY["paysera"]);



    if (isset($_REQUEST['accepturl'])) {
        $invoiceid = checkCbInvoiceID($orderid, $GATEWAY["paysera"]);
        logTransaction($GATEWAY["name"], $_REQUEST, "Successful");
        header("Location: /clientarea.php"); exit();
    } else {

        //Admin username for API
        if (!$GATEWAY["adminusername"]) {
            die("Admin login name not set");
        } else {
            $adminusername = $GATEWAY["adminusername"];
        }

        $orderAmount = '';
        $result      = mysql_query("SELECT total FROM tblinvoices WHERE id = " . $orderid);
        $orderAmount = mysql_result($result, 0);

        if (isset($GATEWAY['convertto'])) {
            $result = mysql_query("SELECT rate FROM tblcurrencies WHERE code = '" . $response['currency'] . "'");
            $rate   = '';
            while ($row = mysql_fetch_array($result)) {
                $rate = $row['rate'];
            }
            $orderAmount *= $rate;
        }


        if ($response['status'] == 1) {

            //Check amount
            if (intval(number_format($orderAmount, 2, '', '')) > $response['amount']) {
                logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "[CALLBACK]Unsuccessful, bad amount");
                exit('Bad amount!');
            }

            //Get real invoice id's if there are multiple invoices paid with one invoice. Check for duplicate callback.


 
$query = "SELECT * from tblorders where invoiceid =".$orderid;

$resultas = mysql_query($query);
            while ($result = mysql_fetch_array($resultas)) { 




                if ($result['status'] == 'Pending') {

                    $value['status']    = "Paid";
                    $value['invoiceid'] = $orderid;
                    localAPI("updateinvoice", $value, $adminusername);

                    $type = localAPI("getinvoice", $value, $adminusername);
                    if ($type['items']['item']['0']['type'] == "Invoice") {
                        mysql_query("UPDATE tblorders SET status = 'Processed' WHERE invoiceid = " . $orderid);
                        mysql_query("UPDATE tblinvoices SET status = 'Paid' WHERE id = " . $orderid); 

                        //In case if user closed redirect from paysera to accept page but payment recieved
                        mysql_query("UPDATE tblinvoiceitems SET amount = '0.00' WHERE invoiceid = " . $orderid);
                        $value['invoiceid'] = $orderid;
                        localAPI("updateinvoice", $value, $adminusername);
                        logTransaction($GATEWAY["name"], $_REQUEST, "[CALLBACK]Successful");
                    } else {
                        mysql_query("UPDATE tblorders SET status = 'Processed' WHERE invoiceid = " . $orderid);
			mysql_query("UPDATE tblinvoices SET status = 'Paid', `datepaid` = '".date("Y-m-d H:i:s")."' WHERE id = " . $orderid); 
	                 addInvoicePayment($orderid,$result['ordernum'],$result['amount'],0,$gatewaymodule);
	                logTransaction($GATEWAY["name"],$_POST,"Successful");


	$result = select_query("tblinvoiceitems", "", array("invoiceid" => $orderid, "type" => "Hosting"));
	$data = mysql_fetch_array($result); //print_r($data); die;
	$relid = $data["relid"];
        $datos = array('Monthly'=> 1, 'Quarterly' => 3, 'Semi-Annually' => 6, 'Annually' => 12, 'Biennially'=> 24, 'Triennially' => 36);

if($data){
	$result2 = select_query("tblorders", "", array("invoiceid" => $orderid));
	$data2 = mysql_fetch_array($result2); 

	$result3 = select_query("tblhosting", "", array("orderid" => $data2["id"]));
	$data3 = mysql_fetch_array($result3);

/*echo $data2["id"]."UPDATE tblhosting SET `nextduedate` = '".date("Y-m-d H:i:s", strtotime($data3['regdate'].'+'.$datos[$data3['billingcycle']].' month'))."',
`nextinvoicedate` = '".date("Y-m-d H:i:s", (strtotime($data3['regdate'].'+'.$datos[$data3['billingcycle']].' month') - (5*24*60*60)))."' WHERE orderid = " . $orderid;
*/
	mysql_query("UPDATE tblhosting SET `nextduedate` = '".date("Y-m-d H:i:s", strtotime($data3['regdate'].'+'.$datos[$data3['billingcycle']].' month'))."',
`nextinvoicedate` = '".date("Y-m-d H:i:s", (strtotime($data3['regdate'].'+'.$datos[$data3['billingcycle']].' month') - (5*24*60*60)))."' WHERE orderid = " . $data2["id"]); 

 $values["accountid"] = $data3['id'];

 $results = localAPI("modulecreate" ,$values ,$adminusername); exit("OK");
}



	$result = select_query("tblinvoiceitems", "", array("invoiceid" => $orderid, "type" => "DomainRegister"));
	$data = mysql_fetch_array($result); //print_r($data); die;
	$relid = $data["relid"];


if($data){ 
 mysql_query("UPDATE tbldomains SET registrar = 'resellerclub' WHERE id = " . $relid); 
 $values["domainid"] = $relid;

 $results = localAPI("domainregister" ,$values ,$adminusername);  exit("OK");

 mysql_query("update `tbldomains` set `nextduedate` = `expirydate`, `nextinvoicedate` = `expirydate` where id = " . $relid); 
}









//	mysql_query("UPDATE tblhosting SET domainstatus = 'Active' WHERE orderid = " . $data2["id"]); 
//update_query("tblhosting", array("domainstatus" => 'Active'), array("orderid" => $data2["id"]));
                        logTransaction($GATEWAY["name"], $_REQUEST, "[CALLBACK]Successful");
                    }
                } else {
                    exit("OK");
                }
            }
        }

    }

    exit('OK');
} catch (Exception $e) {
    logTransaction($GATEWAY["name"], $_REQUEST, "[CALLBACK]Unsuccessful");
    exit(get_class($e) . ': ' . $e->getMessage());
}

function d($d) {
    echo "<pre>";
    print_r($d);
    echo "</pre>";
    die();
}
