<?php

class FinanceTransaction {
  const Adjustment = 0;
  const Income = 1;
  const Expense = 2;
  const Transfer = 3;
  
  public $id;
  public $type;
  public $accountId;
  public $dateTime;
  public $description;
  public $amount = 0;
  public $refId;
  public $refType;
  public $userId;
  
  public static function findByReference($type, $id) {
    global $db;
    $q = $db->prepare("select *"
      . " from finance_transactions"
      . " where refType=? and refId=?");
    $q->bindValue(1, $type);
    $q->bindValue(2, $id);
    $q->execute();
    return $q->fetchObject(FinanceTransaction::class);
  }
  
  public static function findById($id) {
    global $db;
    $q = $db->prepare("select *"
      . " from finance_transactions"
      . " where id=?");
    $q->bindValue(1, $id);
    $q->execute();
    return $q->fetchObject(FinanceTransaction::class);    
  }
  
  public static function save(FinanceTransaction $transaction) {
    global $db;
    
    if (!$transaction->id) {
      $q = $db->prepare('insert into finance_transactions'
        . ' ( type, accountId, description, amount, dateTime,'
        . '   refId, refType, userId)'
        . ' values'
        . ' (:type,:accountId,:description,:amount,:dateTime,'
        . '  :refId,:refType,:userId)'
      );
    }
    else {
      $q = $db->prepare('update finance_transactions set'
        . ' type=:type, accountId=:accountId, description=:description, amount=:amount, dateTime=:dateTime,'
        . ' refId=:refId, refType=:refType, userId=:userId,'
        . ' where id=' . (int)$transaction->id
      );
    }
        
    $q->bindValue(':type', $transaction->type);
    $q->bindValue(':accountId', $transaction->accountId);
    $q->bindValue(':description', $transaction->description);
    $q->bindValue(':amount', $transaction->amount);
    $q->bindValue(':dateTime', $transaction->dateTime);
    $q->bindValue(':refId', $transaction->refId);
    $q->bindValue(':refType', $transaction->refType);
    $q->bindValue(':userId', $transaction->userId);
    $q->execute();
    
    if (!$transaction->id)
      $transaction->id = $db->lastInsertId();
    
    return $q->rowCount();
  }
  
  public static function delete($id) {
    global $db;
    $id = (int)$id;
    $db->query("delete from finance_transactions where id=$id");
  }
}


