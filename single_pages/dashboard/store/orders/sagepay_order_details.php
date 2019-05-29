<?php defined('C5_EXECUTE') or die(_("Access Denied."));
$dh = Core::make('helper/date');

use Concrete\Core\Support\Facade\Url;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price as Price;

?>

<?php
$integrationType = Config::get('hw_community_store_sagepay.Mode');
if ($integrationType == 'FORM') { ?>
    <p class="alert alert-warning text-center"><?php echo t('You have to use sagepay server integration'); ?></p>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">

            <a href="<?= \URL::to('/dashboard/store/orders') ?>"
               class="btn btn-default pull-left"><?= t("Return to orders") ?></a>
        </div>
    </div>
<?php } else { ?>
    <?php $task = $controller->getTask(); ?>

    <?php if ($task == 'view') { ?>
    <div class="ccm-dashboard-header-buttons">
    </div>

    <div class="ccm-dashboard-content-full">


        <?php if (!empty($orderList)) { ?>
            <table class="ccm-search-results-table">
                <thead>
                <tr>
                    <th><a><?= t("Order %s", "#") ?></a></th>
                    <th><a><?= t("Customer Name") ?></a></th>
                    <th><a><?= t("Order Date") ?></a></th>
                    <th><a><?= t("Payment") ?></a></th>
                    <th><a><?= t("Fulfilment Status") ?></a></th>
                    <th><a><?= t("Order Total") ?></a></th>
                    <th><a><?= t("Sage Pay Details") ?></a></th>
                </tr>
                </thead>
                <tbody>

                <?php
                foreach ($orderList

                as $order){
                $cancelled = $order->getCancelled();
                $canstart = '';
                $canend = '';
                if ($cancelled) {
                    $canstart = '<del>';
                    $canend = '</del>';
                }
                ?>
                <tr class="danger">
                    <td><?= $canstart; ?>
                        <a href="<?= URL::to('/dashboard/store/orders/order/', $order->getOrderID()) ?>"><?= $order->getOrderID() ?></a><?= $canend; ?>

                        <?php if ($cancelled) {
                            echo '<span class="text-danger">' . t('Cancelled') . '</span>';
                        }
                        ?>
                    </td>
                    <td><?= $canstart; ?><?php

                        $last = $order->getAttribute('billing_last_name');
                        $first = $order->getAttribute('billing_first_name');

                        if ($last || $first) {
                            echo $last . ", " . $first;
                        } else {
                            echo '<em>' . t('Not found') . '</em>';
                        }

                        ?><?= $canend; ?></td>
                    <td><?= $canstart; ?><?= $dh->formatDateTime($order->getOrderDate()) ?><?= $canend; ?></td>
                    <td>
                        <?php
                        $refunded = $order->getRefunded();
                        $paid = $order->getPaid();

                        if ($refunded) {
                            echo '<span class="label label-warning">' . t('Refunded') . '</span>';
                        } elseif ($paid) {
                            echo '<span class="label label-success">' . t('Paid') . '</span>';
                        } elseif ($order->getTotal() > 0) {
                            echo '<span class="label label-danger">' . t('Unpaid') . '</span>';
                        } else {
                            echo '<span class="label label-default">' . t('Free Order') . '</span>';
                        }
                        ?>
                    </td>
                    <td><?= t(ucwords($order->getStatus())) ?></td>
                    <td><?= $canstart; ?><?= Price::format($order->getSubTotal()) ?><?= $canend; ?></td>
                    <td>
                        <div class="btn-group">
                            <a class="btn btn-primary "
                               href="<?= Url::to('/dashboard/store/orders/sagepay_order_details/sagepaydetails', $order->getOrderID()) ?>"><?= t("View") ?></a>
                        </div>
                    </td>
                    <?php } ?>
                </tbody>
            </table>

        <?php } ?>

        <?php if (empty($orderList)) { ?>
            <br/><p class="alert alert-info"><?= t('No Orders Found'); ?></p>
        <?php } ?>
        <?php if ($paginator->getTotalPages() > 1) { ?>
            <?= $pagination ?>
        <?php } ?>
        <?php } ?>

        <?php if ($task == 'sagepaydetails') { ?>

        <div class="row">
            <div class="col-md-6">
                <h3>
                    <a href="<?= URL::to('/dashboard/store/orders/order/', $order->getOrderID()) ?>"><?= t('Order #'); ?><?= $order->getOrderID() ?></a>
                </h3>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h4><?php echo t('SagePay Payment Details'); ?></h4>
                <table class="table table-striped table-bordered">
                    <tbody>
                    <tr>
                        <td><?php echo('Order Reference') ?> </td>
                        <td><?php echo $order->getTransactionReference(); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo t('Total Authorised') ?> </td>
                        <td><?= Price::format($sagepay->gettransTotal()) ?></td>
                    </tr>
                    <tr>
                        <td><?php echo t('Total Released') ?> </td>
                        <td><?= Price::format($sagepay->getReleased($order->getOrderID())) ?></td>
                    </tr>
                    <?= $token->output('community_store_sagepay'); ?>&nbsp;
                    <tr>
                        <td><?php echo t('Void') ?> </td>
                        <td>
                            <?php $void = $sagepay->getvoidStatus();
                            if ($void) { ?>
                                <span class="void_text"> <?= t("Yes"); ?></span>
                                <?php
                            } else { ?>
                                <span class="void_text"> <?= t("No"); ?></span>
                                <?= $token->output('community_store_sagepay'); ?>&nbsp;
                                <a class="button btn btn-primary" id="button-void"><?php echo t('Void') ?></a> <span
                                        class="btn btn-primary" id="img_loading_void" style="display:none;"><i
                                            class="fa fa-circle-o-notch fa-spin fa-lg"></i></span>
                            <?php };
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo t('Refund') ?></td>
                        <td>
                            <?php $refunded = $sagepay->getrefundStatus();
                            $void = $sagepay->getvoidStatus();
                            if ($refunded OR $void) { ?>
                                <span class="refund_text"><?= t("Yes"); ?></span>
                            <?php } else { ?>
                                <span class="refund_text"><?= t("No"); ?></span>&nbsp;

                                <?php if ($sagepay->getvoidStatus() == 0) { ?>
                                    <input type="text" width="10" id="refund_amount"/>
                                    <a class="button btn btn-primary" id="button-refund"><?php echo t('Refund') ?></a>
                                    <span class="btn btn-primary" id="img_loading_refund" style="display:none;"><i
                                                class="fa fa-circle-o-notch fa-spin fa-lg"></i></span>

                                <?php }
                            };
                            ?>

                        </td>
                    </tr>

                    </tbody>


                </table>
            </div>
        </div>

        <div class="ccm-dashboard-form-actions-wrapper">
            <div class="ccm-dashboard-form-actions">

                <a href="<?= \URL::to('/dashboard/store/orders/sagepay_order_details') ?>"
                   class="btn btn-default pull-left"><?= t("Return to view all orders") ?></a>
            </div>


        </div>
    </div>

    <script type="text/javascript">
        $("#button-void").click(function () {
            var confirmDelete = confirm('Are you sure?');
            if (confirmDelete == true) {
                var ccm_token = $("input[name=ccm_token]").val();
                $.ajax({
                    type: 'POST',
                    dataType: 'text',
                    data: {'order_id': <?php echo $order->getOrderID() ?>, 'ccm_token': ccm_token},
                    cache: false,
                    url: "<?= Url::to('/dashboard/store/orders/sagepay_order_details/void/')?>",
                    beforeSend: function () {
                        $('#button-void').hide();
                        $('#img_loading_void').show();
                    },
                    success: function (data) {
                        json = jQuery.parseJSON(data);
                        if (json.error == false) {
                            $('.void_text').text('<?php echo t("Yes") ?>');

                        }
                        if (json.error == true) {
                            alert(json.msg);
                            $('#button-void').show();
                        }

                        $('#img_loading_void').hide();
                    }
                });
            }
        });


        $("#button-refund").click(function () {
            var confirmDelete = confirm('Are you sure?');
            if (confirmDelete == true) {
                var ccm_token = $("input[name=ccm_token]").val();
                var refund_amount = $("input[name=ccm_token]").val();
                $.ajax({
                    type: 'POST',
                    dataType: 'text',
                    data: {
                        'order_id': <?php echo $order->getOrderID() ?>,
                        'refund_amount': $('#refund_amount').val(),
                        'ccm_token': ccm_token
                    },
                    cache: false,
                    url: "<?= Url::to('/dashboard/store/orders/sagepay_order_details/refund/')?>",
                    beforeSend: function () {
                        $('#button-refund').hide();
                        $('#img_loading_refund').show();
                    },
                    success: function (data) {
                        json = jQuery.parseJSON(data);
                        if (json.error == false) {
                            $('.refund_text').text('<?php echo t("Yes") ?>');

                        }
                        if (json.error == true) {
                            alert(json.msg);
                            $('#button-refund').show();
                        }

                        $('#img_loading_refund').hide();
                    }
                });
            }
        });


    </script>


<?php }
} ?>

