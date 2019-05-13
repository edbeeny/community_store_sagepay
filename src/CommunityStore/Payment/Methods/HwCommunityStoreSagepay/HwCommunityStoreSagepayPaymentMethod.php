<?php

namespace Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore\Payment\Methods\HwCommunityStoreSagepay;

use Core;
use URL;
use Config;
use Session;
use Log;
use FileList;
use File;


use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Cart\Cart as StoreCart;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;

class HwCommunityStoreSagepayPaymentMethod extends StorePaymentMethod
{

    public function dashboardForm()
    {
        $this->set('sagepayTestMode', Config::get('hw_community_store_sagepay.TestMode'));
        $this->set('sagepayCurrency', Config::get('hw_community_store_sagepay.currency'));
        $this->set('sagepayVendorName', Config::get('hw_community_store_sagepay.VendorName'));
        $this->set('sagepayEncryptionPassword', Config::get('hw_community_store_sagepay.EncryptionPassword'));
        $this->set('sagepayVendorEmail', Config::get('hw_community_store_sagepay.VendorEmail'));
        $this->set('sagepayType', Config::get('hw_community_store_sagepay.Type'));

        $txtype = array(
            'PAYMENT' => "PAYMENT",
            'DEFERRED' => "DEFERRED",
            'AUTHENTICATE' => "AUTHENTICATE"
        );
        $this->set('txtype', $txtype);

        $currencies = array(

            'EUR' => "Euro",
            'GBP' => "Pound Sterling"

        );
        $this->set('currencies', $currencies);
        $this->set('form', Core::make("helper/form"));
    }

    public function save(array $data = array())
    {
        Config::save('hw_community_store_sagepay.TestMode', $data['sagepayTestMode']);
        Config::save('hw_community_store_sagepay.currency', $data['sagepayCurrency']);
        Config::save('hw_community_store_sagepay.VendorName', $data['sagepayVendorName']);
        Config::save('hw_community_store_sagepay.EncryptionPassword', $data['sagepayEncryptionPassword']);
        Config::save('hw_community_store_sagepay.VendorEmail', $data['sagepayVendorEmail']);
        Config::save('hw_community_store_sagepay.Type', $data['sagepayType']);

    }

    public function validate($args, $e)
    {
        return $e;
    }

    public function redirectForm()
    {
        $customer = new StoreCustomer();
        $sagepayTestMode = Config::get('hw_community_store_sagepay.TestMode');
        $order = StoreOrder::getByID(Session::get('orderID'));
        $this->set('TxType', Config::get('hw_community_store_sagepay.Type'));
        $this->set('Vendor', Config::get('hw_community_store_sagepay.VendorName'));

        $transactionId = $order->getOrderID() . 'C' . 'S' . strftime("%Y%m%d%H%M%S") . mt_rand(1, 999);

        $this->set('Crypt', $this->getCrypt($transactionId));

        // get unique transaction-ID useful for check payment status

        $order->saveTransactionReference($transactionId);
    }

    public function getCrypt($transactionId)
    {
        $order = StoreOrder::getByID(Session::get('orderID'));
        $customer = new StoreCustomer();

        $cryptString = 'VendorTxCode=' . $transactionId;
        //$cryptString.= '&ReferrerID='.$this->getReferrerID()
        $cryptString .= '&Amount=' . $order->getTotal();
        $cryptString .= '&Currency=' . Config::get('hw_community_store_sagepay.currency');
        $cryptString .= '&Description=' . Config::get('concrete.site');
        $cryptString .= '&SuccessURL=' . URL::to('/checkout/sagepay_success');
        $cryptString .= '&FailureURL=' . URL::to('/checkout/sagepay_failure');
        $cryptString .= '&CustomerName=' . $customer->getValue('billing_first_name');
        $cryptString .= '&CustomerEMail=' . $customer->getEmail();
        $cryptString .= '&VendorEMail=' . Config::get('hw_community_store_sagepay.VendorEmail');

        //$cryptString.= '&SendEMail='.$this->getSendEMail();
        // $cryptString.= '&eMailMessage='.$this->getEMailMessage();

        $cryptString .= '&BillingSurname=' . $customer->getValue('billing_last_name');
        $cryptString .= '&BillingFirstnames=' . $customer->getValue('billing_first_name');
        $cryptString .= '&BillingAddress1=' . $customer->getAddressValue('billing_address', 'address1');
        $cryptString .= '&BillingAddress2=' . $customer->getAddressValue('billing_address', 'address2');
        $cryptString .= '&BillingCity=' . $customer->getAddressValue('billing_address', 'city');
        $cryptString .= '&BillingPostCode=' . $customer->getAddressValue('billing_address', 'postal_code');
        $cryptString .= '&BillingCountry=' . $customer->getAddressValue('billing_address', 'country');
        $cryptString .= '&BillingState=' . $customer->getAddressValue('billing_address', 'state_province');
        $cryptString .= '&BillingPhone=' . $customer->getValue("billing_phone");
        $cryptString .= '&DeliverySurname=' . $customer->getValue('shipping_last_name');
        $cryptString .= '&DeliveryFirstnames=' . $customer->getValue('shipping_first_name');
        $cryptString .= '&DeliveryAddress1=' . $customer->getAddressValue('shipping_address', 'address1');
        $cryptString .= '&DeliveryAddress2=' . $customer->getAddressValue('billing_address', 'address2');
        $cryptString .= '&DeliveryCity=' . $customer->getAddressValue('shipping_address', 'city');
        $cryptString .= '&DeliveryPostCode=' . $customer->getAddressValue('shipping_address', 'postal_code');
        $cryptString .= '&DeliveryCountry=' . $customer->getAddressValue('shipping_address', 'country');
        $cryptString .= '&DeliveryState=' . $customer->getAddressValue('shipping_address', 'state_province');
        $cryptString .= '&DeliveryPhone=' . $customer->getValue("shipping_phone");
        //$cryptString.= '&Basket='.$this->getBasket();

       // $cryptString .= '&BasketXML=' . $this->basketXML(); ** TO DO **
        //$cryptString.= '&AllowGiftAid='.$this->getAllowGiftAid();
        //$cryptString.= '&ApplyAVSCV2='.$this->getApplyAVSCV2();
        //$cryptString.= '&Apply3DSecure='.$this->getApply3DSecure();
        //$cryptString.= '&BillingAgreement='.$this->getBillingAgreement();
        //$cryptString.= '&BasketXML='.$this->getBasketXML();
        //$cryptString.= '&CustomerXML='.$this->getCustomerXML();
        //$cryptString.= '&SurchargeXML='.$this->getSurchargeXML();
        //$cryptString.= '&VendorData='.$this->getVendorData();
        //$cryptString.= '&ReferrerID='.$this->getReferrerID();
        //$cryptString.= '&Language='.$this->getLanguage();
        $cryptString .= '&Website=' . Config::get('concrete.site');

        $datapadded = $this->addPKCS5Padding($cryptString, 16);
        $cryptpadded = "@" . $this->encryptFieldData($datapadded);

        return $cryptpadded;


    }


    public function addPKCS5Padding($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public function encryptFieldData($input)
    {
        $key = Config::get('hw_community_store_sagepay.EncryptionPassword');;

        $enc2 = openssl_encrypt($input, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $key);
        $enc3 = bin2hex($enc2);

        return $enc3;
    }


    public function getAction()
    {
        if (Config::get('hw_community_store_sagepay.TestMode') == true) {
            return "https://test.sagepay.com/gateway/service/vspform-register.vsp";
        } else {
            return "https://live.sagepay.com/gateway/service/vspform-register.vsp";
        }
    }


    public function submitPayment()
    {

        //nothing to do except return true
        return array('error' => 0, 'transactionReference' => '');

    }

    public function validateFailure()
    {
        //Redirects to the checkout form
        $response = \Redirect::to('/checkout');
        $response->send();
        die;

    }

    public function validateCompletion()
    {

        $crypt = $_GET['crypt'];
        $responseArray = $this->decode($crypt);

        if ($responseArray["Status"] === "OK") {
            //Payment Successful! Update the Store
            $transReference = $responseArray["VendorTxCode"];
            $em = \ORM::entityManager();
            $order = $em->getRepository('Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order')->findOneBy(array('transactionReference' => $transReference));
            if ($order) {
                $order->completeOrder();
                $response = \Redirect::to('/checkout/complete');
                $response->send();
                die;
            }

        } elseif ($responseArray["Status"] === "ABORT") {
            return ['error' => 1, 'errorMessage' => t('Transaction Aborted.')];
        } else {
            return ['error' => 1, 'errorMessage' => t('Something went wrong with this transaction.')];
        }
    }


    public function decode($strIn)
    {
        $decodedString = $this->decodeAndDecrypt($strIn);
        parse_str($decodedString, $sagePayResponse);
        return $sagePayResponse;
    }

    public function decodeAndDecrypt($strIn)
    {
        $key = Config::get('hw_community_store_sagepay.EncryptionPassword');

        // Remove the first char which is @ to flag this is AES encrypted and HEX decoding.
        $hexString = substr($strIn, 1);

        // Last minute check to make sure we have data that looks sensible.

        if (!preg_match('/^[0-9a-f]+$/i', $hexString)) {
            throw new \Exception('Invalid "crypt" parameter; not hexadecimal');
        }

        $strIn2 = pack('H*', $hexString);
        $des = openssl_decrypt($strIn2, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $key);

        return $des;

    }

    public function getPaymentMinimum()
    {
        return 0.5;
    }

    public function getName()
    {
        return 'Sage Pay';
    }

    public function isExternal()
    {
        return true;
    }
}

return __NAMESPACE__;
