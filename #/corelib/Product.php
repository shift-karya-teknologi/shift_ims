<?php

class ProductUom
{
  public $id;
  public $productId;
  public $name;
  public $quantity;
}

class Product
{
  const Stocked = 0;
  const NonStocked = 1;
  const Service = 2;
  const ShiftNetVoucher = 255;
  
  const ManualCostingMethod = 0;
  const AverageCostingMethod = 1;
  const LastPurchaseCostingMethod = 2;
  
  public $id;
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
  
  private static $_types = [
    self::Stocked => "Stok",
    self::NonStocked => "Non Stok",
    self::Service => "Jasa",
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
  
  public function getCostingMethodName() {
    return self::$_costingMethods[$this->costingMethod];
  }
  
  public static function getCostingMethods() {
    return self::$_costingMethods;
  }
}