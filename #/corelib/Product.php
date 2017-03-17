<?php

class ProductUom
{
  public $id;
  public $productId;
  public $name;
  public $quantity;
}

class ProductPrice
{
  public $id;
  public $productId;
  
  public $quantityMin = 0;
  public $quantityMax = 0;
  
  public $price1Min = 0;
  public $price1Max = 0;
  
  public $price2Min = 0;
  public $price2Max = 0;
  
  public $price3Min = 0;
  public $price3Max = 0;
}

class Product
{
  const Stocked = 0;
  const NonStocked = 1;
  const Service = 2;
  const MultiPayment = 101;
  const ShiftNetVoucher = 255;
  
  const ManualCostingMethod = 0;
  const AverageCostingMethod = 1;
  const LastPurchaseCostingMethod = 2;
  
  public $id;
  public $categoryId;
  public $type;
  public $name;
  public $active;
  public $quantity;
  public $uom;
  public $costingMethod;
  public $cost;
  public $manualCost;
  public $averageCost;
  public $lastPurchaseCost;
  public $multiPaymentAccountId;
  
  private static $_types = [
    self::Stocked => "Barang",
    self::Service => "Jasa",
    self::MultiPayment => "Multi Payment",
    self::ShiftNetVoucher => "Voucher ShiftNet",
  ];
  
  private static $_costingMethods = [
    self::ManualCostingMethod => "Harga Beli Manual",
    self::AverageCostingMethod => "Harga Beli Rata-rata",
    self::LastPurchaseCostingMethod => "Harga Beli Terakhir",
  ];
  
  public function getStockInfo()
  {
    if ($this->type == self::Stocked)
      return format_number($this->quantity) . ' ' . e($this->uom);
    
    return '';
  }
  
  public function getTypeName() {
    return self::$_types[$this->type];
  }
  
  public static function getTypes() {
    return self::$_types;
  }
  
  public static function typeName($type) {
    return self::$_types[$type];
  }
  
  public function getCostingMethodName() {
    return self::$_costingMethods[$this->costingMethod];
  }
  
  public static function getCostingMethods() {
    return self::$_costingMethods;
  }
  
  public $prices = [];
  
  public function getPriceInfo() {
    
    if (empty($this->prices))
      return '';
    
    if (count($this->prices == 1) && $this->prices[0]->quantityMin == 1 && $this->prices[0]->quantityMax == 0) {
      return $this->_priceInfo($this->prices[0]);
    }
    
    $arr = [];
    
    foreach ($this->prices as $item) {
      $str = '';
      if ($item->quantityMin && $item->quantityMax) {
        $str .= format_number($item->quantityMin) . ' - ' . format_number($item->quantityMax);
      }
      else if ($item->quantityMin != 0 && $item->quantityMax == 0) {
        $str .= '>= ' . format_number($item->quantityMin);
      }
      
      $str .= ' ' . $this->uom . ' : ';
      $str .= $this->_priceInfo($item);
      
      $arr[] = $str;
    }
    
    return implode('<br>', $arr);
  }

  private function _priceInfo($item) {
    $arr = [];    
    for ($i = 1; $i <=3; $i++) {
      $min = "price{$i}Min";
      $max = "price{$i}Max";
    
      $str = '';
      if ($item->$min != $item->$max) {
        $str .= format_number($item->$max) . ' - ' . format_number($item->$min);
      }
      else if ($item->$min != 0){
        $str .= format_number($item->$min);
      }
      else
        continue;
      
      $arr[] = $str;
    }
    
    return implode(' / ', $arr);
  }
}