<?php

namespace Concrete\Package\HwCommunityStoreSagepay\Controller\SinglePage\Dashboard\Store\Orders;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderList as StoreOrderList;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayOrder;
use Concrete\Package\HwCommunityStoreSagepay\Src\SagePay\SagePayServer;
use Config;
use Log;

class SagepayOrderDetails extends DashboardPageController
{

    public function view()
    {
        $this->set('pageTitle', t('Sage Pay Orders'));
        $orderList = new StoreOrderList();
        if ($this->get('keywords')) {
            $orderList->setSearch($this->get('keywords'));
        }
        $this->set('token', $this->app->make('token'));

        $orderList->setItemsPerPage(20);

        $paginator = $orderList->getPagination();
        $pagination = $paginator->renderDefaultView();
        $this->set('orderList', $paginator->getCurrentPageResults());
        $this->set('pagination', $pagination);
        $this->set('paginator', $paginator);
        $this->set('orderStatuses', StoreOrderStatus::getList());
        $this->requireAsset('css', 'communityStoreDashboard');
        $this->requireAsset('javascript', 'communityStoreFunctions');
        $this->set('statuses', StoreOrderStatus::getAll());

    }

    public function sagepaydetails($oID)
    {
        $order = StoreOrder::getByID($oID);

        if ($order) {
            $sagepay = SagePayOrder::getByOrderNo($oID);
            $this->set("order", $order);
            $this->set("sagepay", $sagepay);
        } else {
            $this->redirect('/dashboard/store/orders/sagepay_order_details');
        }


    }

    public function void()
    {
        $token = $this->app->make('token');
        if ($this->request->request->all() && $this->token->validate('community_store_sagepay')) {
            $post = $this->request->request->all();
            $orderNo = $post['order_id'];
            if ($orderNo) {

                $sagepay = new SagePayServer();
                $sagepay->voidPayment($orderNo);

            }
        }
        exit();

    }

    public function refund()
    {
        if ($this->request->request->all() && $this->token->validate('community_store_sagepay')) {
            $post = $this->request->request->all();
            $orderNo = $post['order_id'];
            $refundAmount = $post['refund_amount'];
            if ($orderNo) {
                $sagepay = new SagePayServer();
                $sagepay->refundPayment($orderNo, $refundAmount);
            }

        }
        exit();
    }
}