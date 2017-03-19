<?php

class FinanceAccount {
  public $id;
  public $active = 1;
  public $name;
  public $balance = 0;
  public $users = [];
  
  static function updateBalance($accountId) {
    global $db;
    $db->query('update finance_accounts set'
      . " balance=(select ifnull(sum(amount), 0) from finance_transactions where accountId=$accountId)"
      . " where id=$accountId");
  }
  
  static function findById($accountId) {
    global $db;
    $accountId = (int)$accountId;
    return $db->query("select * from finance_accounts"
      . " where id=$accountId"
    )->fetchObject(FinanceAccount::class);
  }
}


