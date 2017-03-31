<?php

require CORELIB_PATH . '/MultipaymentTransaction.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if ($id) {
  ensure_current_user_can('edit-multipayment-transaction');
  
  $transaction = $db->query("select * from multipayment_transactions where id=$id")
    ->fetchObject(MultiPaymentTransaction::class);
  
  if (!$transaction) {
    $transaction = new MultiPaymentTransaction();
    $transaction->id = $id;
    $_SESSION['FLASH_MESSAGE'] = "Transaksi {$transaction->getCode()} tidak ditemukan.";
    exit(header('Location: ./'));
  }
  
  if ($transaction->salesOrderDetailId) {
    $_SESSION['FLASH_MESSAGE'] = "Transaksi {$transaction->getCode()} tidak dapat diubah.";
    exit(header('Location: ./'));
  }
}
else {
  ensure_current_user_can('add-multipayment-transaction');
  
  $transaction = new MultiPaymentTransaction();
  $transaction->dateTime = date('Y-m-d H:i:s');
}

$types = MultiPaymentTransaction::getTypes();
unset($types[MultiPaymentTransaction::Sales]);
unset($types[MultiPaymentTransaction::Adjustment]);
unset($types[MultiPaymentTransaction::TopUp]);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
  
  if ($action === 'delete') {
    ensure_current_user_can('delete-multipayment-transaction');
    $db->beginTransaction();
    $db->query("delete from multipayment_transactions where id=$id");
    update_multipayment_account_balance($transaction->accountId);
    $db->commit();
    $_SESSION['FLASH_MESSAGE'] = "Transaksi {$transaction->getCode()} telah dihapus.";
    header('Location: ./');
    exit;
  }

  $transaction->userId = $_SESSION['CURRENT_USER']->id;  
  $transaction->amount = (int)str_replace('.', '', $_POST['amount']);
  $transaction->accountId = isset($_POST['accountId']) ? (int)$_POST['accountId'] : null;
  $transaction->dateTime = isset($_POST['dateTime']) ? to_mysql_datetime((string)$_POST['dateTime']) : false;
  $transaction->description = isset($_POST['description']) ? (string)$_POST['description'] : '';
  $transaction->type =  isset($_POST['type']) ? (int)$_POST['type'] : 0;
  
  if (empty($transaction->amount))
    $errors['amount'] = 'Saldo aktual harus diisi.';
  
  if (empty($transaction->accountId))
    $errors['accountId'] = 'Akun harus dipilih.';
  
  if (empty($transaction->dateTime))
    $errors['dateTime'] = 'Tanggal tidak valid.';
  
  if (empty($transaction->description))
    $errors['description'] = 'Deskripsi harus diisi.';
  
  if (!(array_key_exists($transaction->type, $types)))
    $errors['type'] = 'Jenis transaksi tidak valid.';
    
  if (empty($errors)) {
    $db->beginTransaction();
    if (!$id) {
      $q = $db->prepare('insert into multipayment_transactions'
        . ' ( type, accountId, amount, dateTime, description, userId)'
        . ' values'
        . ' (:type,:accountId,:amount,:dateTime,:description,:userId)'
      );
    }
    else {
      $q = $db->prepare('update multipayment_transactions set'
        . ' accountId=:accountId,'
        . ' type=:type,'
        . ' amount=:amount,'
        . ' dateTime=:dateTime,'
        . ' description=:description,'
        . ' userId=:userId'
        . ' where id=:id'
      );
      $q->bindValue(':id', $transaction->id);
    }
    $q->bindValue(':type', $transaction->type);
    $q->bindValue(':accountId', $transaction->accountId);
    $q->bindValue(':amount', $transaction->amount);
    $q->bindValue(':dateTime', $transaction->dateTime);
    $q->bindValue(':description', $transaction->description);
    $q->bindValue(':userId', $transaction->userId);
    $q->execute();
    update_multipayment_account_balance($transaction->accountId);
    $db->commit();

    $_SESSION['FLASH_MESSAGE'] = "Transaksi {$transaction->getCode()} telah disimpan.";
    header('Location: ./');
    exit;
  }
}

$accounts = $db->query('select * from multipayment_accounts where active=1 order by name asc')
  ->fetchAll(PDO::FETCH_OBJ);

render('multipayment/transaction/editor', [
  'transaction' => $transaction,
  'types'    => $types,
  'accounts' => $accounts,
  'errors' => $errors,
]);
