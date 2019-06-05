<?php      

namespace Concrete\Package\HwCommunityStoreSagepay;

use Package;
use Route;
use SinglePage;
use Whoops\Exception\ErrorException;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

defined('C5_EXECUTE') or die(_("Access Denied."));

class Controller extends Package
{
    protected $pkgHandle = 'hw_community_store_sagepay';
    protected $appVersionRequired = '8';
    protected $pkgVersion = '0.9.2';


    protected $pkgAutoloaderRegistries = array(
        'src/CommunityStore' => 'Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore',
        'src/SagePay' => 'Concrete\Package\HwCommunityStoreSagepay\Src\SagePay',
    );

    public function on_start()
    {
        // Check the Routes are in place
        Route::register('/checkout/sagepay_success','\Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore\Payment\Methods\HwCommunityStoreSagepay\HwCommunityStoreSagepayPaymentMethod::validateCompletion');
        Route::register('/checkout/sagepay_failure','\Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore\Payment\Methods\HwCommunityStoreSagepay\HwCommunityStoreSagepayPaymentMethod::validateFailure');
        Route::register('/checkout/sagepayserver_notification','\Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore\Payment\Methods\HwCommunityStoreSagepay\HwCommunityStoreSagepayPaymentMethod::serverNotification');
        Route::register('/checkout/sagepayserver_success','\Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore\Payment\Methods\HwCommunityStoreSagepay\HwCommunityStoreSagepayPaymentMethod::serverSuccess');
        Route::register('/checkout/sagepayserver_failure','\Concrete\Package\HwCommunityStoreSagepay\Src\CommunityStore\Payment\Methods\HwCommunityStoreSagepay\HwCommunityStoreSagepayPaymentMethod::serverfailure');

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
        if (!function_exists('openssl_encrypt')) {
            throw new ErrorException(t('This package requires that openssl_encrypt be installed on your server'));
            exit();
        }
        $installed = Package::getInstalledHandles();
        if(!(is_array($installed) && in_array('community_store',$installed)) ) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('hw_community_store_sagepay','SagePay',$pkg);
        }
        $page = SinglePage::add('/dashboard/store/orders/sagepay_order_details',$pkg);
        $data = array('cName' => 'Sage Pay');
        $page->update($data);
        
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
