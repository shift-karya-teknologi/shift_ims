<?php

class CreditTransaction
{  
  const Payment = 1;
  const AdministrationCost = 2;
  const AdministrationCostAndPayment = 3;
  
  public $id = 0;
  public $accountId;
  public $types = 0;
  public $dateTime;
  public $amount = 0;
  public $userId;
  public $userName;
  
  public function getCode() {
    return '#CRTX-' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
  }

}

