<?php

class MultiPaymentTransaction
{
  const Adjustment = 0;
  const TopUp = 1;
  const Sales = 2;
  const Refund = 3;
  const Profit = 4;
  
  private static $_types = [
    self::Adjustment => "Penyesuaian",
    self::TopUp => "Top Up",
    self::Sales => "Penjualan",
    self::Refund => "Refund",
    self::Profit => "Komisi",
  ];
  
  public $id;
  public $type;
  public $accountId;
  public $accountName;
  public $dateTime;
  public $userId;
  public $userName;
  public $description;
  public $amount;
  public $salesOrderDetailId;
  
  public function getCode() {
    return '#MPX-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
  }
  
  public function getTypeName() {
    return self::$_types[$this->type];
  }
  
  public static function getTypes() {
    return self::$_types;
  }
}

