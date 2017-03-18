<?php

class CreditAccount
{  

  public $id;
  
  public $customerName;
  public $customerAddress;
  public $customerContact;
  public $customerId;
  
  public $productName;
  public $productPrice;
  public $productSerialNumber;
  
  public $administrationCost;
  
  public $dueDate;
  
  public $referralId;
  public $referralName;
  
  public $balance = 0;
  
  public $creationDateTime;
  public $creationUserId;
  public $creationUsername;
  
  public $lastModDateTime;
  public $lastModUserId;
  public $lastModUsername;
  
  public $transactions = [];
  
  public function getCode() {
    return '#CRA-' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
  }

  public static function updateBalance($accountId) {
    global $db;
    $db->query("update credit_accounts set"
      . " balance=(productPrice+administrationCost)-(select ifnull(sum(t.amount), 0) from credit_transactions t where accountId=$accountId)"
      . " where id=$accountId");
  }
}

