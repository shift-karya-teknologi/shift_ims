<?php

class MultiPaymentTransaction
{
  const Adjustment = 0;
  const TopUp = 1;
  const Sales = 2;
  
  public $id;
  public $type;
  public $accountId;
  public $accountName;
  public $dateTime;
  public $userId;
  public $userName;
  public $description;
  public $amount;
}

