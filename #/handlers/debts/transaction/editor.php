<?php

require CORELIB_PATH . '/FinanceTransaction.php';
require CORELIB_PATH . '/FinanceAccount.php';

$accountId = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : 0;
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-debts-transaction');
  $transaction = $db->query('select * from debts_transactions where id=' . $id)->fetchObject();
  if (!$transaction) {
    $_SESSION['FLASH_MESSAGE'] = 'Transaksi tidak ditemukan';
    header('Location: ./');
    exit;
  }
  
  $financeTransaction = FinanceTransaction::findByReference('debts', $transaction->id);
  if ($financeTransaction) {
    $transaction->financeAccountId = (int)$financeTransaction->accountId;
    $transaction->financeTransactionId = (int)$financeTransaction->id;
  }
  else {
    $transaction->financeAccountId = null;
    $transaction->financeTransactionId = null;
  }
}
else {
  ensure_current_user_can('add-debts-transaction');
  $transaction = new stdClass();
  $transaction->id = 0;
  $transaction->amount = 0;
  $transaction->description = '';
  $transaction->accountId = $accountId;
  $transaction->dateTime = date('Y-m-d H:i:s');
  $transaction->financeAccountId = null;
  $transaction->financeTransactionId = null;
}

$accountName = $db->query("select name from debts_accounts where id=$transaction->accountId")->fetchColumn();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? trim($_POST['action']) : '';
  if ($action === 'delete') {
    ensure_current_user_can('delete-finance-transaction');
    $db->beginTransaction();
    $db->query("delete from debts_transactions where id=$transaction->id");
    if ($transaction->financeTransactionId) {
        FinanceTransaction::delete($transaction->financeTransactionId);
        FinanceAccount::updateBalance($transaction->financeTransactionId);
    }
    $db->commit();
    $_SESSION['FLASH_MESSAGE'] = 'Transaksi utang piutang telah dihapus.';
    header('Location: ./?accountId=' . $transaction->accountId);
    exit;
  }
  else if ($action === 'save') {
    $transaction->amount = isset($_POST['amount']) ? (int) trim(str_replace('.', '', $_POST['amount'])) : 0;
    $transaction->description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $transaction->dateTime = to_mysql_datetime(isset($_POST['dateTime']) ? trim((string)$_POST['dateTime']) : '');

    $oldTransactionId = $transaction->financeTransactionId;
    $oldAccountId = $transaction->financeAccountId;
    $transaction->financeAccountId = isset($_POST['financeAccountId']) ? (int)$_POST['financeAccountId'] : 0;
    $newAccountId = $transaction->financeAccountId;

    if (empty($transaction->dateTime))
      $errors['dateTime'] = 'Tanggal tidak valid.';
        
    if (empty($transaction->amount))
      $errors['amount'] = 'Jumlah uang harus diisi.';

    if (empty($errors)) {
      $transaction->userId = $_SESSION['CURRENT_USER']->id;

      $db->beginTransaction();
      if (!$transaction->id) {
        $q = $db->prepare('insert into debts_transactions'
          . ' ( accountId, description, amount, dateTime)'
          . ' values'
          . ' (:accountId,:description,:amount,:dateTime)'
        );
        $q->bindValue(':accountId', $transaction->accountId);
      }
      else {
        $q = $db->prepare('update debts_transactions set'
          . ' description=:description, amount=:amount, dateTime=:dateTime'
          . ' where id=' . (int)$transaction->id
        );
      }
      $q->bindValue(':description', $transaction->description);
      $q->bindValue(':amount', $transaction->amount);
      $q->bindValue(':dateTime', $transaction->dateTime);
      $q->execute();
      
      if (!$transaction->id)
        $transaction->id = $db->lastInsertId();
        
      if ($newAccountId > 0) {
        $financeTransaction = new FinanceTransaction();
        $financeTransaction->id = $newAccountId == $oldAccountId ? $transaction->financeTransactionId : null;
        $financeTransaction->accountId = $transaction->financeAccountId;
        $financeTransaction->type = $transaction->amount > 0 ? FinanceTransaction::Expense : FinanceTransaction::Income;
        $financeTransaction->amount = -$transaction->amount;
        $financeTransaction->dateTime = $transaction->dateTime;
        $financeTransaction->description = $transaction->description;
        $financeTransaction->refType = 'debts';
        $financeTransaction->userId = $transaction->userId ? $transaction->userId : $_SESSION['CURRENT_USER']->id;
        $financeTransaction->refId = $transaction->id;

        FinanceTransaction::save($financeTransaction);
        FinanceAccount::updateBalance($transaction->financeAccountId);
      }

      if ($oldAccountId != $newAccountId && $oldAccountId > 0) {
        FinanceTransaction::delete($oldTransactionId);
        FinanceAccount::updateBalance($oldAccountId);
      }

      $db->commit();

      $_SESSION['FLASH_MESSAGE'] = 'Transaksi utang piutang disimpan.';
      header('Location: ./?accountId=' . $transaction->accountId);
      exit;
    }
  }
}

$accounts = $db->query('select id, name from finance_accounts order by name asc')->fetchAll(PDO::FETCH_OBJ);

render('debts/transaction/editor', [
  'transaction' => $transaction,
  'accountName' => $accountName,
  'accounts' => $accounts,
  'errors' => $errors,
]);
