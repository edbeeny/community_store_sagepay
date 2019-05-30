<?php
namespace Concrete\Package\HwCommunityStoreSagepay\Src\SagePay;

use Core;
use URL;
use Session;
use Config;
use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayForm;
use Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayServer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;

class SagePay
{

    public function generateQuery()
    {
  
        $customer = new StoreCustomer();
        $sagepayTestMode = Config::get('hw_community_store_sagepay.TestMode');
        $order = StoreOrder::getByID(Session::get('orderID'));
        $transactionId =  $order->getOrderID()  . 'C' . 'S' . strftime("%Y%m%d%H%M%S") . mt_rand(1, 999);
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
        //$query.= '&Basket='.$this->getBasket();

        // $query .= '&BasketXML=' . $this->basketXML(); ** TO DO **
        //$query.= '&AllowGiftAid='.$this->getAllowGiftAid();
        //$query.= '&ApplyAVSCV2='.$this->getApplyAVSCV2();
        //$query.= '&Apply3DSecure='.$this->getApply3DSecure();
        //$query.= '&BillingAgreement='.$this->getBillingAgreement();
        //$query.= '&BasketXML='.$this->getBasketXML();
        //$query.= '&CustomerXML='.$this->getCustomerXML();
        //$query.= '&SurchargeXML='.$this->getSurchargeXML();
        //$query.= '&VendorData='.$this->getVendorData();
        //$query.= '&ReferrerID='.$this->getReferrerID();
        //$query.= '&Language='.$this->getLanguage();

        $query .= '&Website=' . Config::get('concrete.site');


       $order->saveTransactionReference($transactionId);
        return $query;
    }
}
