<?php

namespace Concrete\Package\HwCommunityStoreSagepay\Src\SagePay;

use Doctrine\ORM\Mapping as ORM;
use Concrete\Core\Support\Facade\DatabaseORM as dbORM;
use Concrete\Core\Support\Facade\Application;


/**
 * @ORM\Entity
 * @ORM\Table(name="hwcommunitystoresagepayserverorders")
 */
class SagePayOrder
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $oID;

    /** @ORM\Column(type="string",nullable=true) */
    protected $OrderNo;

    /** @ORM\Column(type="string",nullable=true) */
    protected $vendorTxCode;

    /** @ORM\Column(type="string",nullable=true) */
    protected $token;

    /** @ORM\Column(type="string",nullable=true) */
    protected $securitykey;

    /** @ORM\Column(type="string",nullable=true) */
    protected $txAuthNo;

    /** @ORM\Column(type="string",nullable=true) */
    protected $vpsTxId;

    /** @ORM\Column(type="string",nullable=true) */
    protected $bankAuthCode;

    /** @ORM\Column(type="string",nullable=true) */
    protected $declineCode;

    /** @ORM\Column(type="string",nullable=true) */
    protected $fraudResponse;

    /** @ORM\Column(type="string",nullable=true) */
    protected $cardType;

    /** @ORM\Column(type="string",nullable=true) */
    protected $last4Digits;

    /** @ORM\Column(type="integer",nullable=true) */
    protected $voidStatus;

    /** @ORM\Column(type="integer",nullable=true) */
    protected $refundStatus;

    /** @ORM\Column(type="decimal", precision=10, scale=2) */
    protected $transTotal;

    /** @ORM\Column(type="string",nullable=true) */
    protected $transType;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $dateAdded;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $dateModified;


    public function getOrderNo()
    {
        return $this->OrderNo;
    }

    public function setOrderNo($OrderNo)
    {
        $this->OrderNo = $OrderNo;
    }

    public function getvendorTxCode()
    {
        return $this->vendorTxCode;
    }

    public function setvendorTxCode($vendorTxCode)
    {
        $this->vendorTxCode = $vendorTxCode;
    }

    public function gettoken()
    {
        return $this->token;
    }

    public function settoken($token)
    {
        $this->token = $token;
    }

    public function getsecuritykey()
    {
        return $this->securitykey;
    }

    public function setsecuritykey($securitykey)
    {
        $this->securitykey = $securitykey;
    }

    public function gettxAuthNo()
    {
        return $this->txAuthNo;
    }

    public function settxAuthNo($txAuthNo)
    {
        $this->txAuthNo = $txAuthNo;
    }

    public function getvpsTxId()
    {
        return $this->vpsTxId;
    }

    public function setvpsTxId($vpsTxId)
    {
        $this->vpsTxId = $vpsTxId;
    }

    public function getbankAuthCode()
    {
        return $this->bankAuthCode;
    }

    public function setbankAuthCode($bankAuthCode)
    {
        $this->bankAuthCode = $bankAuthCode;
    }

    public function getdeclineCode()
    {
        return $this->declineCode;
    }

    public function setdeclineCode($declineCode)
    {
        $this->declineCode = $declineCode;
    }

    public function getfraudResponse()
    {
        return $this->fraudResponse;
    }

    public function setfraudResponse($fraudResponse)
    {
        $this->fraudResponse = $fraudResponse;
    }

    public function getcardType()
    {
        return $this->cardType;
    }

    public function setcardType($cardType)
    {
        $this->cardType = $cardType;
    }

    public function getlast4Digits()
    {
        return $this->last4Digits;
    }

    public function setlast4Digits($last4Digits)
    {
        $this->last4Digits = $last4Digits;
    }

    public function gettransTotal()
    {
        return $this->transTotal;
    }

    public function settransTotal($transTotal)
    {
        $this->transTotal = $transTotal;
    }

    public function gettransType()
    {
        return $this->transType;
    }

    public function settransType($transType)
    {
        $this->transType = $transType;
    }

    public function getvoidStatus()
    {
        return $this->voidStatus;
    }

    public function setvoidStatus($voidStatus)
    {
        $this->voidStatus = $voidStatus;
    }

    public function getrefundStatus()
    {
        return $this->refundStatus;
    }

    public function setrefundStatus($refundStatus)
    {
        $this->refundStatus = $refundStatus;
    }

    public function getdateAdded()
    {
        return $this->dateAdded;
    }

    public function setdateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;
    }

    public function getdateModified()
    {
        return $this->dateModified;
    }

    public function setdateModified($dateModified)
    {
        $this->dateModified = $dateModified;
    }

    public function getByOrderNo($id)
    {
        $em = dbORM::entityManager();

        return $em->getRepository(get_class())->findOneBy(['OrderNo' => $id]);
    }

    public function getReleased($oID)
    {
        $app = Application::getFacadeApplication();
        $db = $app->make('database')->connection();
        $rows = $db->GetOne("SELECT sum(transTotal) FROM hwcommunitystoresagepayserverorders WHERE OrderNo=? and (transType=? OR transType=?)", array($oID, 'PAYMENT', 'REFUND'));

        return $rows;
    }


    public function save()
    {
        $em = \ORM::entityManager();
        $em->persist($this);
        $em->flush();
    }


}