<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>

<div class="form-group">
    <label><?= t('Test Mode')?></label>
    <?= $form->select('sagepayTestMode', array(false=>'Live',true=>'Test Mode'), $sagepayTestMode); ?>
</div>

<div class="form-group">
    <label><?= t("Sage Pay Vendor Name")?></label>
    <input type="text" name="sagepayVendorName" value="<?= $sagepayVendorName?>" class="form-control">
</div>

<div class="form-group">
    <?= $form->label('sagepayEncryptionPassword',t("Sage Pay Encryption Password")); ?>
    <input type="text" name="sagepayEncryptionPassword" value="<?= $sagepayEncryptionPassword?>" class="form-control">
</div>

<div class="form-group">
    <label><?= t("Sage Pay Vendor Email")?></label>
    <input type="text" name="sagepayVendorEmail" value="<?= $sagepayVendorEmail?>" class="form-control">
</div>

<div class="form-group">
    <?= $form->label('sagepayType',t("Type")); ?>
    <?= $form->select('sagepayType', $txtype, $sagepayTypey?$sagepayType:'PAYMENT');?>
</div>

<div class="form-group">
    <?= $form->label('sagepayCurrency',t("Currency")); ?>
    <?= $form->select('sagepayCurrency', $currencies, $sagepayCurrency?$sagepayCurrency:'GBP');?>
</div>
