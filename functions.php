<?php

require('get-common/eBaySession.php'); //include session file for curl operations

function printLabel($prodname, $toname, $address1, $address2, $address3, $orderid) {
global $labelOutputdir, $printerDevice, $labelTemplate, $printerDelay;
	$handle = fopen($labelTemplate, "r");
	$template = fread($handle, filesize($labelTemplate));
	fclose($handle);

	// Replace Product Name
	$template = str_replace('%PRODUCT%', $prodname, $template);

	// Replace To Name
	$template = str_replace('%TONAME%', $toname, $template);

	// Replace Address1
	$template = str_replace('%ADDRESS1%', $address1, $template);

	// Replace Address2
	$template = str_replace('%ADDRESS2%', $address2, $template);

	// Replace Address3
	$template = str_replace('%ADDRESS3%', $address3, $template);

	// Write populated label file to disk
	$labelfile = fopen($labelOutputdir."\\".$orderid.".prn", "w") or die("Unable to open file!");
	fwrite($labelfile, $template);
	fclose($labelfile);

	echo exec('print /D:'.$printerDevice.' '.$labelOutputdir.'\\'.$orderid.'.prn');

	// slow the faq down so you don't overrun the Zebra printer buffer and lock the printer up dawg
	sleep($printerDelay);

}

function markShipped($orderid) {
	global $userToken, $devID, $appID,$certID,$serverUrl,$compatabilityLevel,$siteID;
	// mark as shipped
	
	///Build the request Xml string
	$requestXmlBody2 = '<?xml version="1.0" encoding="utf-8" ?>';
	$requestXmlBody2 .= '<CompleteSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
	$requestXmlBody2 .= '<OrderID>'.$orderid.'</OrderID>';
	$requestXmlBody2 .= "<Shipped>true</Shipped>";
	$requestXmlBody2 .= "<RequesterCredentials><eBayAuthToken>$userToken</eBayAuthToken></RequesterCredentials>";
	$requestXmlBody2 .= '</CompleteSaleRequest>';

	//Create a new eBay session with all details pulled in from included keys.php
	$session2 = new eBaySession($userToken, $devID, $appID, $certID, $serverUrl, $compatabilityLevel, $siteID, 'CompleteSale');

	//send the request and get response
	$responseXml2 = $session2->sendHttpRequest($requestXmlBody2);
	if (stristr($responseXml2, 'HTTP 404') || $responseXml2 == '')

	die('Error sending request');
}