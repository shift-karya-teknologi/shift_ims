<?php

require CORELIB_PATH . '/CreditAccount.php';
require CORELIB_PATH . '/CreditTransaction.php';

$accountId = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : 0;
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-credit-transaction');
  $item = $db->query("select * from credit_transactions where id=$id")->fetchObject(CreditTransaction::class);
}
else if ($accountId) {
  ensure_current_user_can('add-credit-transaction');
  $item = new CreditTransaction;
  $item->accountId = $accountId;
}

$account = $db->query("select * from credit_accounts where id=$item->accountId")->fetchObject(CreditAccount::class);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
  if ($action === 'delete') {
    ensure_current_user_can('delete-credit-transaction');
    $db->beginTransaction();
    $db->query("delete from credit_transactions where id=$item->id");
    CreditAccount::updateBalance($item->accountId);
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = "Pembayaran {$item->getCode()} telah dihapus.";
    exit(header("Location: ../account/view?id=$item->accountId"));
  }
  else if ($action === 'save') {
    $item->amount = isset($_POST['amount']) ? (int)str_replace('.', '', trim((string)$_POST['amount'])) : 0;
    $item->types  = isset($_POST['types']) ? (int)$_POST['types'] : 0;
    
    if (empty($item->amount))
      $errors['amount'] = 'Masukkan jumlah pembayaran.';
    
    if (empty($errors)) {
      $db->beginTransaction();
      
      if (!$item->id) {
        $q = $db->prepare('insert into credit_transactions'
          . ' ( accountId, types, dateTime, amount, userId)'
          . ' values'
          . ' (:accountId,:types,:dateTime,:amount,:userId)'
        );
        $q->bindValue(':accountId', $item->accountId);
        $q->bindValue(':userId', $_SESSION['CURRENT_USER']->id);
        $q->bindValue(':dateTime', date('Y-m-d H:i:s'));
      }
      else {
        $q = $db->prepare('update credit_transactions set'
          . ' amount=:amount, types=:types where id=' . $item->id
        );
      }
      $q->bindValue(':types', $item->types);
      $q->bindValue(':amount', $item->amount);
      $q->execute();
      CreditAccount::updateBalance($item->accountId);
      if (!$item->id) $item->id = $db->lastInsertId();
      $db->commit();
      
      $_SESSION['FLASH_MESSAGE'] = "Pembayaran {$item->getCode()} telah disimpan.";
      exit(header("Location: ../account/view?id=$item->accountId"));
    }
  }
}

render('credit/transaction/editor', [
  'item' => $item,
  'account' => $account,
  'errors' => $errors,
]);