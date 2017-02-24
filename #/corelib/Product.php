<?php

abstract class ProductTypes
{
  const NonStocked = 0;
  const Stocked = 1;
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
  public $name;
  public $quantity;
}

class Product
{
  public $id;
  public $type;
  public $name;
  public $active;
  public $baseUom;
  public $costingMethod;
}