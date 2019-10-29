<?php

namespace Concrete\Package\HwCommunityStoreSagepay\Src\SagePay;


use Concrete\Core\Support\Facade\Log;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderList as StoreOrderList;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatusHistory as StoreOrderStatusHistory;
use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayOrder;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Support\Facade\Session;
use Concrete\Core\Support\Facade\Config;
use Concrete\Core\User\User;
use Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;

class SagePayServer
{

    Public function processPayment($sagepayurl, $query)
    {

        set_time_limit(60);
        $output = array();
        $curlSession = curl_init();
        $ttl = 30;
        $query .= '&TxType=' . Config::get('hw_community_store_sagepay.Type');
        $query .= '&NotificationURL=' . URL::to('/checkout/sagepayserver_notification');

        $result = SagePayServer::sendCurl($sagepayurl, $query);
        if ($result['Status'] == 'OK') {

            //Add details to the database
            SagePayServer::addOrderDetails($result);

            header('Location: ' . $result['NextURL']);
            exit();
        }
    }

    public function voidPayment($orderID)
    {

        $sagepayOrder = SagePayOrder::getByOrderNo($orderID);

        if (Config::get('hw_community_store_sagepay.TestMode') == true) {
            $sagepayurl = "https://test.sagepay.com/gateway/service/void.vsp";
        } else {
            $sagepayurl = "https://live.sagepay.com/gateway/service/void.vsp";
        }

        $query = '&TxType=VOID';
        $query .= '&Vendor=' . Config::get('hw_community_store_sagepay.VendorName');
        $query .= '&VendorTxCode=' . $sagepayOrder->getvendorTxCode();
        $query .= '&VPSTxId=' . $sagepayOrder->getvpsTxId();
        $query .= '&SecurityKey=' . $sagepayOrder->getsecuritykey();
        $query .= '&TxAuthNo=' . $sagepayOrder->gettxAuthNo();


        $result = SagePayServer::sendCurl($sagepayurl, $query);


        if ($result['Status'] == 'OK') {

            //set Void Status = 1 against order
            $now = new \DateTime();
            $OrderDetails = SagePayOrder::getByOrderNo($orderID);
            $OrderDetails->setvoidStatus(1);
            $OrderDetails->setdateModified($now);
            $OrderDetails->save();


            $order = StoreOrder::getByID($orderID);
            $user = new User();

            $order->setRefunded(new \DateTime());
            $order->setRefundedByUID($user->getUserID());
            $order->setRefundReason('Sage Pay Void');
            $order->save();

            $results = array(
                'error' => false,
                'msg' => 'Order Voided Successfully'
            );
        } else {
            $results = array(
                'error' => true,
                'msg' => 'Cannot Void Transaction'
            );

        }
        echo json_encode($results);

    }


    public function refundPayment($orderID, $refundAmount)
    {

        $sagepayOrder = SagePayOrder::getByOrderNo($orderID);

        if (Config::get('hw_community_store_sagepay.TestMode') == true) {
            $sagepayurl = "https://test.sagepay.com/gateway/service/refund.vsp";
        } else {
            $sagepayurl = "https://live.sagepay.com/gateway/service/refund.vsp";
        }
        $query = '&TxType=REFUND';
        $query .= '&Vendor=' . Config::get('hw_community_store_sagepay.VendorName');
        $query .= '&VendorTxCode=' . $sagepayOrder->getvendorTxCode() . rand();
        $query .= '&Currency=' . Config::get('hw_community_store_sagepay.currency');
        $query .= '&Amount=' . $refundAmount;
        $query .= '&Description=Refund from website';
        $query .= '&RelatedVPSTxId=' . $sagepayOrder->getvpsTxId();
        $query .= '&RelatedVendorTxCode=' . $sagepayOrder->getvendorTxCode();
        $query .= '&RelatedSecurityKey=' . $sagepayOrder->getsecuritykey();
        $query .= '&RelatedTxAuthNo=' . $sagepayOrder->gettxAuthNo();


        $result = SagePayServer::sendCurl($sagepayurl, $query);

        if ($result['Status'] == 'OK') {
            //set Refunded Status = 1 against order
            $now = new \DateTime();
            $OrderDetails = SagePayOrder::getByOrderNo($orderID);
            $OrderDetails->setrefundStatus(1);
            $OrderDetails->setdateModified($now);
            $OrderDetails->save();
            $order = StoreOrder::getByID($orderID);
            $user = new User();

            $order->setRefunded(new \DateTime());
            $order->setRefundedByUID($user->getUserID());
            $order->setRefundReason('Sage Pay Refunded (' . $refundAmount . ')');
            $order->save();

            $results = array(
                'error' => false,
                'msg' => 'Order Refunded Successfully'
            );
        } else {
            $results = array(
                'error' => true,
                'msg' => 'Cannot Refund Transaction'
            );

        }
        echo json_encode($results);

    }


    Public function sendCurl($sagepayurl, $query)
    {

        set_time_limit(60);
        $output = array();
        $curlSession = curl_init();
        $ttl = 30;

        curl_setopt($curlSession, CURLOPT_URL, $sagepayurl);
        curl_setopt($curlSession, CURLOPT_HEADER, 0);
        curl_setopt($curlSession, CURLOPT_POST, 1);
        curl_setopt($curlSession, CURLOPT_POSTFIELDS, $query);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlSession, CURLOPT_TIMEOUT, $ttl);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, 0);

        $rawresponse = curl_exec($curlSession);

        if (curl_getinfo($curlSession, CURLINFO_HTTP_CODE) !== 200) {
            $output['Status'] = "FAIL";
            $output['StatusDetails'] = "Server Response: " . curl_getinfo($curlSession, CURLINFO_HTTP_CODE);
            $output['Response'] = $rawresponse;

            return $output;
        }

        curl_close($curlSession);
        $response = SagePayServer::queryStringToArray($rawresponse, "\r\n");

        $result = array_merge($output, $response);
        return $result;
    }


    public function addOrderDetails($query)
    {
        $order = StoreOrder::getByID(Session::get('orderID'));
        $orderid = $order->getOrderID();
        $now = new \DateTime();

        $OrderDetails = new SagePayOrder();
        $OrderDetails->setOrderNo($orderid);
        $OrderDetails->setvpsTxId($query['VPSTxId']);
        $OrderDetails->setsecuritykey($query['SecurityKey']);
        $OrderDetails->settransTotal($order->getSubTotal());
        $OrderDetails->settransType(Config::get('hw_community_store_sagepay.Type'));
        $OrderDetails->setdateAdded($now);
        $OrderDetails->setdateModified($now);

        $OrderDetails->save();
    }

    public function updateOrderDetails($id, $query, $ordertotal)
    {

        $now = new \DateTime();
        $OrderDetails = SagePayOrder::getByOrderNo($id);
        $OrderDetails->settoken($query['Token']);
        $OrderDetails->settxAuthNo($query['TxAuthNo']);
        $OrderDetails->setvendorTxCode($query['VendorTxCode']);
        $OrderDetails->setbankAuthCode($query['BankAuthCode']);
        $OrderDetails->setdeclineCode($query['DeclineCode']);
        $OrderDetails->setfraudResponse($query['FraudResponse']);
        $OrderDetails->setcardType($query['CardType']);
        $OrderDetails->setlast4Digits($query['Last4Digits']);
        $OrderDetails->settransTotal($ordertotal);

        $OrderDetails->setdateModified($now);
        $OrderDetails->save();
    }


    static public function queryStringToArray($data, $delimeter = "&")
    {
        // Explode query by delimiter
        $pairs = explode($delimeter, $data);
        $queryArray = array();

        // Explode pairs by "="
        foreach ($pairs as $pair) {
            $keyValue = explode('=', $pair);

            // Use first value as key
            $key = array_shift($keyValue);

            // Implode others as value for $key
            $queryArray[$key] = implode('=', $keyValue);
        }
        return $queryArray;
    }
}
