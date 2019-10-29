<?php

namespace Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore\Payment\Methods\HwCommunityStoreSagepay;

use Concrete\Core\Routing\Redirect;
use Concrete\Core\Support\Facade\Application;
use Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayOrder;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Support\Facade\Config;
use Concrete\Core\Support\Facade\Session;
use \Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayForm;
use \Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayServer;
use \Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePay;
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
        $this->set('sagepayMode', Config::get('hw_community_store_sagepay.Mode'));

        $txtype = array(
            'PAYMENT' => "PAYMENT",

           /**TODO It should work but test is not setup for these types of transactions
            * * 'DEFERRED' => "DEFERRED", **
            * * 'AUTHENTICATE' => "AUTHENTICATE" * */
        );
        $this->set('txtype', $txtype);

        $txtmode = array(
            'SERVER' => "SERVER",
            'FORM' => "FORM"
        );
        $this->set('txtmode', $txtmode);

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
        Config::save('hw_community_store_sagepay.Mode', $data['sagepayMode']);

    }

    public function validate($args, $e)
    {
        return $e;
    }


    public function redirectForm()
    {

        $this->set('TxType', Config::get('hw_community_store_sagepay.Type'));
        $this->set('Vendor', Config::get('hw_community_store_sagepay.VendorName'));

        If (Config::get('hw_community_store_sagepay.Mode') == 'SERVER') {

            if (Config::get('hw_community_store_sagepay.TestMode') == true) {
                $sagepayurl = "https://test.sagepay.com/gateway/service/vspserver-register.vsp";
            } else {
                $sagepayurl = "https://live.sagepay.com/gateway/service/vspserver-register.vsp";
            }
            $querys = SagePay::generateQuery();
            SagePayServer::processPayment($sagepayurl, $querys);

        } else {
            $querys = SagePay::generateQuery();
            $this->set('Crypt', SagePayForm::getCrypt($querys));
        }
    }

    public function getAction()
    {
        If (Config::get('hw_community_store_sagepay.Mode') == 'SERVER') {
            //returns nothing as we get the url when it redirects the form.
            return "#";
        } else {
            if (Config::get('hw_community_store_sagepay.TestMode') == true) {
                return "https://test.sagepay.com/gateway/service/vspform-register.vsp";
            } else {
                return "https://live.sagepay.com/gateway/service/vspform-register.vsp";
            }
        }
    }


    public function submitPayment()
    {
        If (Config::get('hw_community_store_sagepay.Mode') == 'SERVER') {
            return array('error' => 0, 'transactionReference' => '');
        } else {
            //nothing to do except return true
            return array('error' => 0, 'transactionReference' => '');
        }


    }

    public function validateFailure()
    {
        //Redirects to the checkout form
        $response = \Redirect::to('/checkout');
        $response->send();
    }

    public function validateCompletion()
    {

        If (Config::get('hw_community_store_sagepay.Mode') == 'SERVER') {
            //Do nothing
        } else {
            $crypt = $_GET['crypt'];
            $responseArray = SagePayForm::decode($crypt);

            if ($responseArray["Status"] === "OK") {
                //Payment Successful! Update the Store
                $transReference = $responseArray["VendorTxCode"];
                $em = \ORM::entityManager();
                $order = $em->getRepository('Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order')->findOneBy(array('transactionReference' => $transReference));
                if ($order) {
                    $order->completeOrder();
                    $response = \Redirect::to('/checkout/complete');
                    $response->send();
                }
            } elseif ($responseArray["Status"] === "ABORT") {
                return ['error' => 1, 'errorMessage' => t('Transaction Aborted.')];
            } else {
                return ['error' => 1, 'errorMessage' => t('Something went wrong with this transaction.')];
            }
        }
    }

    public function serverNotification()
    {

        //Get the response from SagePay
        $vtxData = filter_input_array(INPUT_POST);

        $transReference = $vtxData['VendorTxCode'];
        $status = $vtxData['Status'];
        $VPSSignature = $vtxData['VPSSignature'];

        //Add checks to make sure payment hasn't been changed. We could add more in the future.
        //Check the order exists from the VendorTxCode returned by SagePay, if it doesn't respond with error

        $em = \ORM::entityManager();
        $order = $em->getRepository('Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order')->findOneBy(array('transactionReference' => $transReference));

        if (!$order) {

            $r = URL::to('checkout/sagepayserver_failure');
            $end_ln = chr(13) . chr(10);
            echo "Status=INVALID" . $end_ln;
            echo "StatusDetail= VendorTxCode not be matched. Order might have been tampered with." . $end_ln;
            echo "RedirectURL=" . $r . $end_ln;

            //Stop and return to payment page with error
            exit;
        }

        $sagepayOrder = SagePayOrder::getByOrderNo($order);
        $securitykey = $sagepayOrder->getsecuritykey();


        //Construct a concatenated POST string hash.
        //Check out signature and compare it against the contents of the VPSSignature field in the POST.
        $strMessage =
            $vtxData['VPSTxId'] . $vtxData['VendorTxCode'] . $vtxData['Status']
            . $vtxData['TxAuthNo'] . Config::get('hw_community_store_sagepay.VendorName')
            . $vtxData['AVSCV2'] . $securitykey
            . $vtxData['AddressResult'] . $vtxData['PostCodeResult'] . $vtxData['CV2Result']
            . $vtxData['GiftAid'] . $vtxData['3DSecureStatus']
            . $vtxData['CAVV'] . $vtxData['AddressStatus'] . $vtxData['PayerStatus']
            . $vtxData['CardType'] . $vtxData['Last4Digits']
            . $vtxData['DeclineCode'] . $vtxData['ExpiryDate']
            . $vtxData['FraudResponse'] . $vtxData['BankAuthCode'];

        $MySignature = strtoupper(md5($strMessage));

        if ($MySignature !== $VPSSignature) {
            // Message that record has been tampered with.
            $r = URL::to('checkout/sagepayserver_failure');
            $end_ln = chr(13) . chr(10);
            echo "Status=INVALID" . $end_ln;
            echo "StatusDetail= Notification has been tampered with" . $end_ln;
            echo "RedirectURL=" . $r . $end_ln;

            //Stop and return to payment page with error
            exit;

        }

        //Check the status if not ok return to Payment Page and notify SagePay of failure
        if ($status != "OK" && $status != "REGISTERED" && $status != "AUTHENTICATED") {
            $r = URL::to('checkout/sagepayserver_failure');
            $end_ln = chr(13) . chr(10);
            echo "Status=INVALID" . $end_ln;
            echo "StatusDetail= status invalid" . $end_ln;
            echo "RedirectURL=" . $r . $end_ln;

            //Stop and return to payment page with error
            exit;

        }

        //Update Sagepay table with results
        SagePayServer::updateOrderDetails($order->getOrderID(), $vtxData, $order->getTotal());

        //Completes the Order
        $order->completeOrder();

        $end_ln = chr(13) . chr(10);
        $r = URL::to('checkout/complete');

        echo "Status=OK" . $end_ln;
        echo "RedirectURL=" . $r . $end_ln;

    }

    public function serverSuccess()
    {
        //Do nothing
    }

    public function serverfailure()
    {
        Session::set('paymentErrors', 'Something went wrong with the payment, please try again.');
        $response = \Redirect::to('/checkout/failed#payment');
        $response->send();
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
