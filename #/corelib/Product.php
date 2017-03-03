<?php

abstract class ProductTypes
{
  const Stocked = 0;
  const NonStocked = 1;
  const Service = 2;
  const ShiftNetVoucher = 255;
}

abstract class ProductCostingMethods
{
  const Manual = 0;
  const Average = 1;
  const LastPurchase = 2;
}

class ProductUom
{
  public $id;
  public $productId;
  public $name;
  public $quantity;
}

class Product
{
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
  
  public function getStockInfo()
  {
    if ($this->type == ProductTypes::Stocked)
      return format_number($this->quantity) . ' ' . e($this->uom);
    
    return '';
  }
}