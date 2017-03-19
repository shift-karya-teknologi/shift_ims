<?php

require CORELIB_PATH . '/FinanceAccount.php';
require CORELIB_PATH . '/FinanceTransaction.php';

$accountId = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : 0;
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-finance-transaction');
  $transaction = $db->query('select * from finance_transactions where id=' . $id)->fetchObject(FinanceTransaction::class);
  if (!$transaction) {
    $_SESSION['FLASH_MESSAGE'] = 'Transaksi tidak ditemukan';
    header('Location: ./');
    exit;
  }
}
else {
  ensure_current_user_can('add-finance-transaction');
  $transaction = new FinanceTransaction();
  $transaction->accountId = $accountId;
  $transaction->refType = '';
  $transaction->refId = '';
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? trim($_POST['action']) : '';
  if ($action === 'delete') {
    $db->beginTransaction();
    FinanceTransaction::delete($transaction->id);
    FinanceAccount::updateBalance($transaction->accountId);
    $db->commit();
    $_SESSION['FLASH_MESSAGE'] = 'Transaksi kas telah dihapus.';
    header('Location: ./view?id=' . $transaction->accountId);
    exit;
  }
  else if ($action === 'save') {
    $transaction->amount = isset($_POST['amount']) ? (int) trim(str_replace('.', '', $_POST['amount'])) : 0;
    $transaction->type = isset($_POST['type']) ? (int)$_POST['type'] : 0;
    $transaction->description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if (!($transaction->type == FinanceTransaction::TYPE_INCOME || $transaction->type == FinanceTransaction::TYPE_EXPENSE))
      $errors['type'] = 'Jenis transaksi tidak valid.';
    
    if (empty($transaction->amount))
      $errors['amount'] = 'Jumlah uang harus diisi.';

    if (empty($transaction->description))
      $errors['description'] = 'Deskripsi harus diisi.';

    if (empty($errors)) {
      $transaction->dateTime = date('Y-m-d H:i:s');
      $transaction->userId = $_SESSION['CURRENT_USER']->id;
      if ($transaction->type === FinanceTransaction::TYPE_EXPENSE)
        $transaction->amount = -$transaction->amount;

      $db->beginTransaction();

      FinanceTransaction::save($transaction);
      FinanceAccount::updateBalance($transaction->accountId);

      $db->commit();

      $_SESSION['FLASH_MESSAGE'] = 'Transaksi kas disimpan.';
      header('Location: ./view?id=' . $transaction->accountId);
      exit;
    }
  }
}

render('finance/account/transaction-editor', [
  'transaction' => $transaction,
  'errors'   => $errors,
]);
