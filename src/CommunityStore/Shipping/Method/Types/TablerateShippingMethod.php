<?php
  namespace Concrete\Package\CommunityStoreShippingTablerate\Src\CommunityStore\Shipping\Method\Types;

  use Package;
  use Core;
  use Database;
  use File;
  use Concrete\Package\CommunityStore\Src\CommunityStore\Product\Product as StoreProduct;
  use Concrete\Package\CommunityStore\Src\CommunityStore\Shipping\Method\ShippingMethodTypeMethod as StoreShippingMethodTypeMethod;
  use Concrete\Package\CommunityStore\Src\CommunityStore\Cart\Cart as StoreCart;
  use Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
  use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
  use Concrete\Package\CommunityStore\Src\CommunityStore\Shipping\Method\ShippingMethodOffer as StoreShippingMethodOffer;
  use Doctrine\ORM\Mapping as ORM;

  /**
   * @ORM\Entity
   * @ORM\Table(name="CommunityStoreTablerateMethods")
   */
  class TablerateShippingMethod extends StoreShippingMethodTypeMethod{
    public function getShippingMethodTypeName() {
        return t('Table Rate');
    }
    /**
     * @ORM\Column(type="string")
     */
    protected $rateType;
    /**
     * @ORM\Column(type="float")
     */
    protected $minimumAmount;
    /**
     * @ORM\Column(type="float")
     */
    protected $maximumAmount;
    /**
     * @ORM\Column(type="integer")
     */
    protected $csvFile;

    public function setRateType($rateType)
    {
        $this->rateType = $rateType;
    }
    public function setMinimumAmount($minAmount)
    {
        $this->minimumAmount = $minAmount;
    }
    public function setMaximumAmount($maxAmount)
    {
        $this->maximumAmount = $maxAmount;
    }
    public function setCsvFile($csvFile)
    {
        $this->csvFile = $csvFile;
    }

    public function getRateType()
    {
        return $this->rateType;
    }
    public function getMinimumAmount()
    {
        return $this->minimumAmount;
    }
    public function getMaximumAmount()
    {
        return $this->maximumAmount;
    }
    public function getCsvFile()
    {
        return $this->csvFile;
    }

    public function dashboardForm($shippingMethod = null)
    {
        $this->set('form', Core::make("helper/form"));
        $this->set('fileUpload', Core::make('helper/concrete/file_manager'));
        $this->set('smt', $this);
        $pkg = Package::getByHandle("community_store_shipping_tablerate");
        $pkgconfig = $pkg->getConfig();
        $this->set('config', $pkgconfig);
        if (is_object($shippingMethod)) {
            $smtm = $shippingMethod->getShippingMethodTypeMethod();
        } else {
            $smtm = new self();
        }

        $this->set("smtm", $smtm);
    }
    public function addMethodTypeMethod($data){
      //$sm->add('tablerate','Table Rate',$pkg);
      $pkg = Package::getByHandle('community_store_shipping_tablerate');
      $pkgID = $pkg->getPackageID();
      $sm = new self();

      $sm->setRateType($data['rateType']);
      $sm->setMinimumAmount($data['minimumAmount']);
      $sm->setMaximumAmount($data['maximumAmount']);
      if(!empty($data['csvFile'])){
        $sm->setCsvFile($data['csvFile']);
      }else{
        $sm->setCsvFile(0);
      }

      $sm->save();

      if(!empty($data['csvFile']) && $data['csvFile'] != 0){
        $this->insertCsvFile($data, $sm);
      }

      return $sm;
    }
    public function update($data){
      $sm = $this;
      $sm->setRateType($data['rateType']);
      $sm->setMinimumAmount($data['minimumAmount']);
      $sm->setMaximumAmount($data['maximumAmount']);
      if(!empty($data['csvFile'])){
        $sm->setCsvFile($data['csvFile']);
      }
      $sm->save();

      //$em = Database::get()->getEntityManager();
      //$em->persist($sm);
      //$em->flush();

      if(!empty($data['csvFile']) && $data['csvFile'] != 0){
        $this->insertCsvFile($data, $sm);
      }

      return $sm;
    }
    public function insertCsvFile($data, $sm){
      $csvFile = File::getByID($data['csvFile']);
      $fv = $csvFile->getRecentVersion();
      $path = $fv->getUrl();
      $csvAsArray = array_map('str_getcsv', file($path));

      $smID = $sm->getShippingMethodID();
      $db = Database::connection();
      $dData = array();
      $dData[] = $smID;
      $db->Execute('delete from CommunityStoreTablerateConditions where trID=?', $dData);
      $skipFirst = 0;
      foreach($csvAsArray as $row){
        if($skipFirst != 0){
          $uData = array();
          $uData[] = $smID;
          $uData[] = $row[0];
          $uData[] = $row[1];
          $uData[] = $row[3];
          $uData[] = $row[4];
          $db->Execute('insert into CommunityStoreTablerateConditions (trID, country, state, checkValue, shippingPrice) values (?,?,?,?,?)', $uData);
        }else{
          $skipFirst++;
        }
      }
    }

    public function isEligible(){
      //three checks - within countries, price range, and condition
      if ($this->isWithinRange()) {
          if ($this->isWithinSelectedCountries()) {
              if($this->hasMinimumCondition()){
                  return true;
              }else{
                  return false;
              }
          } else {
              return false;
          }
      } else {
          return false;
      }
    }
    public function isWithinRange()
    {
        $subtotal = StoreCalculator::getSubTotal();
        $max = $this->getMaximumAmount();
        if ($max!=0) {
            if ($subtotal >= $this->getMinimumAmount() && $subtotal <= $this->getMaximumAmount()) {
                return true;
            } else {
                return false;
            }
        } elseif ($subtotal >= $this->getMinimumAmount()) {
            return true;
        } else {
            return false;
        }
    }
    public function isWithinSelectedCountries()
    {
        $customer = new StoreCustomer();
        $custCountry = $customer->getValue('shipping_address')->country;
        $db = Database::connection();
        $cData = array();
        $cData[] = $this->getShippingMethodID();
        $cData[] = $custCountry;
        $checkRow = $db->fetchAll('select * from CommunityStoreTablerateConditions where trID=? and country=?', $cData);
        if (!empty($checkRow)) {
            return true;
        } else {
            return false;
        }
    }
    public function hasMinimumCondition(){
      //check on database level if a condition value can be retrieved
      $shippableItems = StoreCart::getShippableItems();
      $typeOfCondition = $this->getRateType();
      if($typeOfCondition == "amountItems"){
        $checkValue = $this->getTotalProducts($shippableItems);
      }else if($typeOfCondition == "amountWeight"){
        $checkValue = $this->getTotalWeight($shippableItems);
      }else{
        $checkValue = $this->getSubtotalPrice($shippableItems);
      }

      $db = Database::connection();
      $cData = array();
      $cData[] = $this->getShippingMethodID();
      $cData[] = $checkValue;
      $checkRow = $db->fetchAssoc('select * from CommunityStoreTablerateConditions where trID=? and checkValue <= ? order by checkValue desc', $cData);

      if(!empty($checkRow['shippingPrice']) && $checkRow['shippingPrice'] != 0){
        return true;
      }else{
        return false;
      }
    }

    public function getRate(){
      //get price of the shipment
      $shippingTotal = 0;

      $customer = new StoreCustomer();
      $custCountry = $customer->getValue('shipping_address')->country;
      $shippableItems = StoreCart::getShippableItems();
      $typeOfCondition = $this->getRateType();
      if($typeOfCondition == "amountItems"){
        $checkValue = $this->getTotalProducts($shippableItems);
      }else if($typeOfCondition == "amountWeight"){
        $checkValue = $this->getTotalWeight($shippableItems);
      }else{
        $checkValue = $this->getSubtotalPrice($shippableItems);
      }

      $db = Database::connection();
      $cData = array();
      $cData[] = $this->getShippingMethodID();
      $cData[] = $custCountry;
      $cData[] = $checkValue;
      $checkRow = $db->fetchAssoc('select * from CommunityStoreTablerateConditions where trID=? and country=? and checkValue <= ? order by checkValue desc', $cData);

      if(!empty($checkRow['shippingPrice']) && $checkRow['shippingPrice'] != 0){
        $shippingTotal = $checkRow['shippingPrice'];
      }

      return $shippingTotal;
    }
    public function getTotalWeight($shippableItems)
    {
        $totalWeight = 0;
        foreach ($shippableItems as $item) {
            $product = StoreProduct::getByID($item['product']['pID']);
            if ($product->isShippable()) {
                $totalProductWeight = $item['product']['qty'] * $product->getWeight();
                $totalWeight = $totalWeight + $totalProductWeight;
            }
        }
        return $totalWeight;
    }
    public function getTotalProducts($shippableItems)
    {
        $totalProducts = 0;
        foreach ($shippableItems as $item) {
            $totalProducts = $totalProducts + $item['product']['qty'];
        }
        return $totalProducts;
    }
    public function getSubtotalPrice($shippableItems)
    {
        $totalPrice = 0;
        foreach ($shippableItems as $item) {
          $product = StoreProduct::getByID($item['product']['pID']);

          if ($product->isShippable()) {
              $productPrice = $product->getActivePrice();
              $totalProductPrice = $item['product']['qty'] * $productPrice;
              $totalPrice = $totalPrice + $totalProductPrice;
          }
        }
        return $totalPrice;
    }

    public function getOffers() {
        $offers = array();
        // for each sub-rate, create a StoreShippingMethodOffer
        $offer = new StoreShippingMethodOffer();
        // then set the rate
        $offer->setRate($this->getRate());
        // add it to the array
        $offers[] = $offer;

        return $offers;
    }
  }
?>
