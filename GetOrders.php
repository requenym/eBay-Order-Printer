<?php
/*  © 2013 eBay Inc., All Rights Reserved */ 
/* Licensed under CDDL 1.0 -  http://opensource.org/licenses/cddl1.php */

require('config.php');
require('functions.php');


//Time with respect to GMT
//by default retreive orders in last 30 minutes
$CreateTimeFrom = gmdate("Y-m-d\TH:i:s",time()-1153200); //current time minus 30 minutes
//echo $CreateTimeFrom;
$CreateTimeTo = gmdate("Y-m-d\TH:i:s", time());


//If you want to hard code From and To timings, Follow the below format in "GMT".
//$CreateTimeFrom = YYYY-MM-DDTHH:MM:SS; //GMT
//$CreateTimeTo = YYYY-MM-DDTHH:MM:SS; //GMT


///Build the request Xml string
$requestXmlBody = '<?xml version="1.0" encoding="utf-8" ?>';
$requestXmlBody .= '<GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
$requestXmlBody .= '<DetailLevel>ReturnAll</DetailLevel>';
$requestXmlBody .= "<NumberOfDays>1</NumberOfDays>";
$requestXmlBody .= '<OrderRole>Seller</OrderRole><OrderStatus>Completed</OrderStatus>';
$requestXmlBody .= "<RequesterCredentials><eBayAuthToken>$userToken</eBayAuthToken></RequesterCredentials>";
$requestXmlBody .= '</GetOrdersRequest>';

//Create a new eBay session with all details pulled in from included keys.php
$session = new eBaySession($userToken, $devID, $appID, $certID, $serverUrl, $compatabilityLevel, $siteID, 'GetOrders');

//send the request and get response
$responseXml = $session->sendHttpRequest($requestXmlBody);
if (stristr($responseXml, 'HTTP 404') || $responseXml == '')
    die('Error sending request');

//Xml string is parsed and creates a DOM Document object
$responseDoc = new DomDocument();
$responseDoc->loadXML($responseXml);

//get any error nodes
$errors = $responseDoc->getElementsByTagName('Errors');
$response = simplexml_import_dom($responseDoc);
$entries = $response->PaginationResult->TotalNumberOfEntries;


//if there are error nodes
if ($errors->length > 0) {
    echo '<P><B>eBay returned the following error(s):</B>';
    //display each error
    //Get error code, ShortMesaage and LongMessage
    $code = $errors->item(0)->getElementsByTagName('ErrorCode');
    $shortMsg = $errors->item(0)->getElementsByTagName('ShortMessage');
    $longMsg = $errors->item(0)->getElementsByTagName('LongMessage');
    
    //Display code and shortmessage
    echo '<P>', $code->item(0)->nodeValue, ' : ', str_replace(">", "&gt;", str_replace("<", "&lt;", $shortMsg->item(0)->nodeValue));
    
    //if there is a long message (ie ErrorLevel=1), display it
    if (count($longMsg) > 0)
        echo '<BR>', str_replace(">", "&gt;", str_replace("<", "&lt;", $longMsg->item(0)->nodeValue));
}else { //If there are no errors, continue
    if(isset($_GET['debug']))
    {  
       header("Content-type: text/xml");
       print_r($responseXml);
    }else
     {  //$responseXml is parsed in view.php
        header("Content-Type: text/plain; charset=UTF-8");
//$response = simplexml_import_dom($responseDoc);
if ($entries == 0) {
    echo "Sorry No entries found in the Time period requested. Change CreateTimeFrom/CreateTimeTo and Try again";
} else {
    $orders = $response->OrderArray->Order;
//var_dump($orders);
    if ($orders != null) {
        foreach ($orders as $order) {
            if (!isset($order->ShippedTime)) {

            echo "Order Information:\n";
            echo "OrderID ->" . $order->OrderID . "\n";
            echo "Order -> Status:" . $orderStatus = $order->OrderStatus . "\n";

            //if the order is completed, print details

                // get the checkout message left by the buyer, if any
                if ($order->BuyerCheckoutMessage) {
                    echo "BuyerCheckoutMsg : " . $order->BuyerCheckoutMessage . "\n";
                }
				
                // get the external transaction information - if payment is made via PayPal, then this is the PayPal transaction info
                $externalTransaction = $order->ExternalTransaction;
                if ($externalTransaction) {
                    echo "ExternalTransactionID  : " . $externalTransaction->ExternalTransactionID . "\n";
                }

                // get the shipping service selected by the buyer
                $ShippingServiceSelected = $order->ShippingServiceSelected;
                if($ShippingServiceSelected){
                echo "Shipping Service Selected  : " . $ShippingServiceSelected->ShippingService . " \n";
                $ShippingCostAttr = $ShippingServiceSelected->ShippingServiceCost->attributes();
                echo "ShippingServiceCost  : " . $ShippingServiceSelected->ShippingServiceCost . " " . $ShippingCostAttr["currencyID"] . "\n";
                }
               
                // get the buyer's shipping address 
                $shippingAddress = $order->ShippingAddress;
                $citystatezip = $shippingAddress->CityName. ','. $shippingAddress->StateOrProvince . ' ' . $shippingAddress->PostalCode;
                if ($shippingAddress->Street1 != null) {
                    $address1 =  $shippingAddress->Street1;
                }
                if ($shippingAddress->Street2 != '') {
                    $address2 =  $shippingAddress->Street2;
					$address3 =  $citystatezip;
				}
				else {
					$address2 = $citystatezip;
					$address3 = $shippingAddress->CountryName;
				}
				
                if ($shippingAddress->CountryName != null) {
                    $address .= 
                            $shippingAddress->CountryName . ".\n";
                }

                $transactions = $order->TransactionArray;
                if ($transactions) {
                    echo "Transaction Array \n";
                    // iterate through each transaction for the order
                    foreach ($transactions->Transaction as $transaction) {
                        // get the OrderLineItemID, Quantity, buyer's email and SKU

                        echo "OrderLineItemID : " . $transaction->OrderLineItemID . "\n";
                        echo "QuantityPurchased  : " . $transaction->QuantityPurchased . "\n";
                        echo "Buyer Email : " . $transaction->Buyer->Email . "\n";
                        $prodname = $transaction->Item->Title;
    
                        // if the item is listed with variations, get the variation SKU
                        $VariationSKU = $transaction->Variation->SKU;
                        if ($VariationSKU != null) {
                            echo "Variation SKU  : " . $VariationSKU. "\n";
                        }
                        echo "TransactionID: " . $transaction->TransactionID . "\n";
                        $transactionPriceAttr = $transaction->TransactionPrice->attributes();
                        echo "TransactionPrice : " . $transaction->TransactionPrice . " " . $transactionPriceAttr["currencyID"] . "\n";
                        echo "Platform : " . $transaction->Platform . "\n";
                    }
                }

            }//end if
            if($ShippingServiceSelected){
				if ($ShippingServiceSelected->ShippingService == 'ShippingMethodStandard') {
					// USPS first class non-parcel label
					//printLabel(substr($prodname,0,25), $shippingAddress->Name, $address1, $address2, $address3, $order->ShippingDetails->SellingManagerSalesRecordNumber);
					markShipped($order->OrderID);
				}
				else {
					// real tracking label type...
				}
			}

			
        }
    }else{
	echo "No Order Found";
	}
}

    }
} 


?>
