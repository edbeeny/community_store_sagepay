<?php      

namespace Concrete\Package\HwCommunityStoreSagepay;

use Package;
use Route;
use Whoops\Exception\ErrorException;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

defined('C5_EXECUTE') or die(_("Access Denied."));

class Controller extends Package
{
    protected $pkgHandle = 'hw_community_store_sagepay';
    protected $appVersionRequired = '5.7.2';
    protected $pkgVersion = '0.9.0';

    public function on_start()
    {
        // Check the Routes are in place
        Route::register('/checkout/sagepay_success','\Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore\Payment\Methods\HwCommunityStoreSagepay\HwCommunityStoreSagepayPaymentMethod::validateCompletion');
        Route::register('/checkout/sagepay_failure','\Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore\Payment\Methods\HwCommunityStoreSagepay\HwCommunityStoreSagepayPaymentMethod::validateFailure');
    }

    public function getPackageDescription()
    {
        return t("Sage Pay Payment Method for Community Store");
    }

    public function getPackageName()
    {
        return t("Sage Pay Payment Method");
    }
    
    public function install()
    {
        $installed = Package::getInstalledHandles();
        if(!(is_array($installed) && in_array('community_store',$installed)) ) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('hw_community_store_sagepay','SagePay',$pkg);
        }
        
    }
    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('hw_community_store_sagepay');
        if ($pm) {
            $pm->delete();
        }
        $pkg = parent::uninstall();
    }


}
?>