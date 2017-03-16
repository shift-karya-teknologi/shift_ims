<?php

ensure_current_user_can('adjust-multipayment-account');

require CORELIB_PATH . '/MultipaymentTransaction.php';

$transaction = new MultiPaymentTransaction();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $actualAmount = (int)str_replace('.', '', $_POST['amount']);
  $transaction->accountId = isset($_POST['accountId']) ? (int)$_POST['accountId'] : null;
  
  if (empty($actualAmount))
    $errors['amount'] = 'Saldo aktual harus diisi.';
  
  if (empty($transaction->accountId))
    $errors['accountId'] = 'Akun harus dipilih.';
    
  if (empty($errors)) {
    $storedAmount = $db->query('select sum(amount) from multipayment_transactions where accountId=' . $transaction->accountId)
      ->fetch(PDO::FETCH_COLUMN);
    $balance = $actualAmount - $storedAmount;
    $transaction->amount = $balance;
    
    $db->beginTransaction();
    $q = $db->prepare('insert into multipayment_transactions'
      . ' ( type, accountId, amount, dateTime, description, userId)'
      . ' values'
      . ' (:type,:accountId,:amount,:dateTime,:description,:userId)');
    $q->bindValue(':type', MultiPaymentTransaction::Adjustment);
    $q->bindValue(':amount', $transaction->amount);
    $q->bindValue(':accountId', $transaction->accountId);
    $q->bindValue(':userId', $_SESSION['CURRENT_USER']->id);
    $q->bindValue(':dateTime', date('Y-m-d H:i:s'));
    $q->bindValue(':description', 'Penyesuaian Saldo');
    $q->execute();
    update_multipayment_account_balance($transaction->accountId);
    $db->commit();

    $_SESSION['FLASH_MESSAGE'] = 'Penyesuaian saldo berhasil.';
    header('Location: ./');
    exit;
  }
}
$accounts = $db->query('select * from multipayment_accounts where active=1 order by name asc')
  ->fetchAll(PDO::FETCH_OBJ);
render('pos/multipayment-transaction/adjust', [
  'transaction' => $transaction,
  'accounts' => $accounts,
  'errors' => $errors,
]);
