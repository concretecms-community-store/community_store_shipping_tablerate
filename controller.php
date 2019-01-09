<?php
namespace Concrete\Package\CommunityStoreShippingTablerate;

use Concrete\Package\CommunityStore\Src\CommunityStore\Shipping\Method\ShippingMethodType as StoreShippingMethodType;
use Package;
use Page;
use Database;
use Whoops\Exception\ErrorException;

class controller extends Package{

  protected $pkgHandle = 'community_store_shipping_tablerate';
  protected $appVersionRequired = '8.0';
  protected $pkgVersion = '2.0';

    protected $pkgAutoloaderRegistries = array(
        'src/CommunityStore' => 'Concrete\Package\CommunityStoreShippingTablerate\Src\CommunityStore',
    );

  public function getPackageDescription(){
    return t("Adds the table rate shipping method to Concrete5 Community store.");
  }

  public function getPackageName(){
    return t("Community Store Table Rate shipping");
  }

  public function install(){
    $installed = Package::getInstalledHandles();
    if(!(is_array($installed) && in_array('community_store',$installed)) ) {
      throw new ErrorException(t('This package requires that Community Store be installed'));
    } else {
      $pkg = parent::install();
      $sm = new StoreShippingMethodType();
      $sm->add('tablerate','Table Rate',$pkg);
    }
  }

  public function uninstall(){
    StoreShippingMethodType::getByHandle('tablerate')->delete();
    $db = Database::connection();
    $db->Execute('drop table CommunityStoreTablerateConditions');
    $pkg = parent::uninstall();
  }
}
?>
