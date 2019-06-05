<?php

namespace Concrete\Package\HwCommunityStoreSagepay\Src\SagePay;

use Concrete\Package\CommunityStore\Src\CommunityStore\Tax\Tax as StoreTax;
use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price as StorePrice;
use Core;
use URL;
use Session;
use Config;
use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayForm;
use Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayServer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Cart\Cart as StoreCart;
use DOMDocument;

class SagePay
{

    public function generateQuery()
    {

        $customer = new StoreCustomer();
        $sagepayTestMode = Config::get('hw_community_store_sagepay.TestMode');
        $order = StoreOrder::getByID(Session::get('orderID'));
        $transactionId = $order->getOrderID() . 'C' . 'S' . strftime("%Y%m%d%H%M%S") . mt_rand(1, 999);
        //$transactionId = '1';
        $query = '&VPSProtocol=3.00';
        $query .= '&Vendor=' . Config::get('hw_community_store_sagepay.VendorName');
        $query .= '&VendorTxCode=' . $transactionId;
        //$query.= '&ReferrerID='.$this->getReferrerID()
        $query .= '&Amount=' . number_format(StoreCalculator::getGrandTotal(), 2, '.', '');
        $query .= '&Currency=' . Config::get('hw_community_store_sagepay.currency');
        $query .= '&Description=' . Config::get('concrete.site');
        $query .= '&CustomerName=' . $customer->getValue('billing_first_name');
        $query .= '&CustomerEMail=' . $customer->getEmail();
        $query .= '&VendorEMail=' . Config::get('hw_community_store_sagepay.VendorEmail');

        //$query.= '&SendEMail='.$this->getSendEMail();
        // $query.= '&eMailMessage='.$this->getEMailMessage();

        $query .= '&BillingSurname=' . $customer->getValue('billing_last_name');
        $query .= '&BillingFirstnames=' . $customer->getValue('billing_first_name');
        $query .= '&BillingAddress1=' . $customer->getAddressValue('billing_address', 'address1');
        $query .= '&BillingAddress2=' . $customer->getAddressValue('billing_address', 'address2');
        $query .= '&BillingCity=' . $customer->getAddressValue('billing_address', 'city');
        $query .= '&BillingPostCode=' . $customer->getAddressValue('billing_address', 'postal_code');
        $query .= '&BillingCountry=' . $customer->getAddressValue('billing_address', 'country');

        if ($customer->getAddressValue('billing_address', 'country') == 'US') {
            $query .= '&BillingState=' . $customer->getAddressValue('billing_address', 'state_province');
        }
        $query .= '&BillingPhone=' . $customer->getValue("billing_phone");
        $query .= '&DeliverySurname=' . $customer->getValue('shipping_last_name');
        $query .= '&DeliveryFirstnames=' . $customer->getValue('shipping_first_name');
        $query .= '&DeliveryAddress1=' . $customer->getAddressValue('shipping_address', 'address1');
        $query .= '&DeliveryAddress2=' . $customer->getAddressValue('billing_address', 'address2');
        $query .= '&DeliveryCity=' . $customer->getAddressValue('shipping_address', 'city');
        $query .= '&DeliveryPostCode=' . $customer->getAddressValue('shipping_address', 'postal_code');
        $query .= '&DeliveryCountry=' . $customer->getAddressValue('shipping_address', 'country');


        if ($customer->getAddressValue('shipping_address', 'country') == 'US') {
            $query .= '&DeliveryState=' . $customer->getAddressValue('shipping_address', 'state_province');
        }
        $query .= '&DeliveryPhone=' . $customer->getValue("shipping_phone");
        //$query .= '&Basket=' . SagePay::getBasket();

        $query .= '&BasketXML=' . SagePay::getBasketXml();

        //$query.= '&AllowGiftAid='.$this->getAllowGiftAid();
        //$query.= '&ApplyAVSCV2='.$this->getApplyAVSCV2();
        //$query.= '&Apply3DSecure='.$this->getApply3DSecure();
        //$query.= '&BillingAgreement='.$this->getBillingAgreement();
        //$query.= '&CustomerXML='.$this->getCustomerXML();
        //$query.= '&SurchargeXML='.$this->getSurchargeXML();
        //$query.= '&VendorData='.$this->getVendorData();
        //$query.= '&ReferrerID='.$this->getReferrerID();
        //$query.= '&Language='.$this->getLanguage();

        $query .= '&Website=' . Config::get('concrete.site');


        $order->saveTransactionReference($transactionId);
        return $query;
    }


    public function getBasketXml()
    {
        $dom = new DOMDocument();
        $dom->formatOutput = false;
        $basket = $dom->createElement('basket');
        $basket = $dom->appendChild($basket);

        $order = StoreOrder::getByID(Session::get('orderID'));
        $items = $order->getOrderItems();

        foreach ($items as $item) {
            $tax = $item->getTax();
            $qty = $item->getQty();
            $product = $item->getProductName();

            //Sage Pay rejected product names with certain characters, strip them out.
            $product = preg_replace('/[^A-Za-z0-9\. -]/', '', $product);
            $productpricepaid = number_format($item->getPricePaid(), 2, '.', '');
            if ($tax > 0) {
                $totaltax = number_format($item->getTax(), 2, '.', '');
                $taxamount = number_format($totaltax / $qty, 2, '.', '');
                $productprice = number_format($productpricepaid, 2, '.', '');
            } else {
                $totaltax = number_format($item->getTaxIncluded(), 2, '.', '');
                $taxamount = number_format($totaltax / $qty, 2, '.', '');
                $productprice = number_format($productpricepaid - $taxamount, 2, '.', '');
            }

            $unitgross = number_format($productprice + $taxamount, 2, '.', '');
            $totalGross = number_format($unitgross * $qty, 2, '.', '');

            $item = $basket->appendChild($dom->createElement("item"));
            $item->appendChild($dom->createElement("description", $product));
            $item->appendChild($dom->createElement('quantity', $qty));
            $item->appendChild($dom->createElement('unitNetAmount', $productprice));
            $item->appendChild($dom->createElement('unitTaxAmount', $taxamount));
            $item->appendChild($dom->createElement('unitGrossAmount', $unitgross));
            $item->appendChild($dom->createElement('totalGrossAmount', $totalGross));

        }

        $totals = StoreCalculator::getTotals();
        $shippingTotal = number_format($totals['shippingTotal'], 2, '.', '');
        $basket->appendChild($dom->createElement('deliveryGrossAmount', $shippingTotal));

        return $dom->saveXML($dom->documentElement);


    }
}
