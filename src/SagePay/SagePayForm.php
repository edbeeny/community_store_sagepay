<?php
namespace Concrete\Package\HwCommunityStoreSagepay\Src\SagePay;

use Core;
use URL;
use Session;
use Config;
use Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;

class SagePayForm
{


    public function getCrypt($query)
    {
        $order = StoreOrder::getByID(Session::get('orderID'));
        $customer = new StoreCustomer();

        $query .= '&SuccessURL=' . URL::to('/checkout/sagepay_success');
        $query .= '&FailureURL=' . URL::to('/checkout/sagepay_failure');


        $datapadded = SagePayForm::addPKCS5Padding($query, 16);
        $cryptpadded = "@" . SagePayForm::encryptFieldData($datapadded);

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


    public function decode($strIn)
    {
        $decodedString = SagePayForm::decodeAndDecrypt($strIn);
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
}