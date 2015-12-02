<?php
/**
 * Magento order exporter for WebXel Order Importer for Sage 
 * Based on the examples of creating the XML data that Order Importer for Sage expects when its configured to download orders from an HTTP web page, found at: http://www.orderimporter.co.uk/Downloads/Downloads.html
 *
 * @version    1.0.1
 * @author     Wojciech Szymanski
 */
require_once('../app/Mage.php');

function AddFieldToXML($FieldName, $Value)
{
	$FindStr = "&";
	$NewStr  = "&amp;";	
	$Result = str_replace($FindStr, $NewStr, $Value);
	echo "\t\t<$FieldName>$Result</$FieldName>\n";
}

define("MODE", "TEST");

$log_file = 'in_sage_'.date("j.n.Y").'.txt';

Mage::app();

$orders = Mage::getResourceModel('sales/order_collection')
			->addFieldToSelect('*')
			->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
			->addFieldToFilter('status', 'complete')
			->setOrder('created_at', 'desc');

//Write action to txt log
$log  = "-------------------------".PHP_EOL.
        "Start @: ". date('m/d/Y h:i:s a', time()) .PHP_EOL.
        "-------------------------".PHP_EOL;
//-
file_put_contents('log/' . $log_file, $log, FILE_APPEND);

//begin outputing XML
echo "<?xml version=\"1.0\" standalone=\"yes\" ?>\n";
echo "<DsOrders xmlns=\"http://www.tempuri.org/DsOrderInfo.xsd\">\n";

foreach ($orders as $order) {
    // $email = $order->getCustomerEmail();
    // echo $email . "\n";

    echo "\t<Orders>\n";

    $real_order_id = $order->getRealOrderId();

    //Required Fields
	AddFieldToXML("CustomerID", $order->getCustomerId());	
	AddFieldToXML("OrderID",  $real_order_id);
	AddFieldToXML("OrderDate", $order->getCreatedAt());


	$shipping_name = $order->getShippingAddress()->getData('firstname') . " " . $order->getShippingAddress()->getData('lastname');
	$shipping_company = $order->getShippingAddress()->getData('company');
	$shipping_street = $order->getShippingAddress()->getData('street');
	$shipping_city = $order->getShippingAddress()->getData('city');
	$shipping_region = $order->getShippingAddress()->getData('region');
	$shipping_postcode = $order->getShippingAddress()->getData('postcode');


	// dbug show what info is available in shipping address
	// print_r($order->getShippingAddress()->getData());


	//Optional Fields (Used)
	AddFieldToXML("DelContactName", $shipping_name);
	AddFieldToXML("DelCompany", $shipping_company);
	AddFieldToXML("DelAddress1", $shipping_street);
	AddFieldToXML("DelAddress2", "");
	AddFieldToXML("DelTown", $shipping_city);
	AddFieldToXML("DelCounty", $shipping_region);
	AddFieldToXML("DelPostCode", $shipping_postcode);

	echo "\t</Orders>\n";

	// add shipping cost
	echo "\t<OrderItems>\n";

	//Required Fields
	AddFieldToXML("OrderID", $real_order_id);
	AddFieldToXML("Description", $order->getShippingDescription());
	AddFieldToXML("Price", $order->getShippingAmount());
	AddFieldToXML("ProductCode", "S1");
	AddFieldToXML("Quantity", 1);	

	//Optional Fields (Not Used)
	//AddFieldToXML("TaxCode", PutValueHere);
			
	echo "\t</OrderItems>\n";

	$ordered_items = $order->getAllItems();
	Foreach($ordered_items as $item){
	    //item detail

	    echo "\t<OrderItems>\n";
		
		//Required Fields
		AddFieldToXML("OrderID", $real_order_id);
		AddFieldToXML("Description", $item->getName());
		AddFieldToXML("Price", $item->getPrice());
		AddFieldToXML("ProductCode", $item->getSku());
		AddFieldToXML("Quantity", $item->getQtyOrdered());	
		
		//Optional Fields (Not Used)
	    //AddFieldToXML("TaxCode", PutValueHere);
					
		echo "\t</OrderItems>\n";
	}

	// change order status to: IN_SAGE
	// Please remember to create custom order state. Login into Magento backend, go to System->Order Statuses->Create New Status 
	
	if (MODE == 'LIVE'){
		// $order->setData('state', "in_sage");
		$order->setStatus("in_sage");
		$history = $order->addStatusHistoryComment('Order exported to sage  and status was changed to In Sage by our automation tool.', false);
		$history->setIsCustomerNotified(false);
		$order->save();
	}

	//Write action to txt log
    $log  = "Order exported to sage: " . $real_order_id . PHP_EOL;
    //-
    file_put_contents('log/' . $log_file, $log, FILE_APPEND);
	
}
	

//finish outputing XML
echo "</DsOrders>";

//Write action to txt log
$log  = "-------------------------".PHP_EOL.
        "Done @: ". date('m/d/Y h:i:s a', time()) .PHP_EOL.
        "-------------------------".PHP_EOL;
//-
file_put_contents('log/' . $log_file, $log, FILE_APPEND);

?> 