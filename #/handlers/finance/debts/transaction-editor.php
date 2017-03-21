<?php

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
}
else {
  ensure_current_user_can('add-debts-transaction');
  $transaction = new stdClass();
  $transaction->id = 0;
  $transaction->amount = 0;
  $transaction->remarks = '';
  $transaction->accountId = $accountId;
  $transaction->date = date('Y-m-d');
}

$accountName = $db->query("select name from debts_accounts where id=$transaction->accountId")->fetchColumn();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? trim($_POST['action']) : '';
  if ($action === 'delete') {
    ensure_current_user_can('delete-finance-transaction');
    $db->beginTransaction();
    $db->query("delete from debts_transactions where id=$transaction->id");
    $db->commit();
    $_SESSION['FLASH_MESSAGE'] = 'Transaksi utang piutang telah dihapus.';
    header('Location: ./view?id=' . $transaction->accountId);
    exit;
  }
  else if ($action === 'save') {
    $transaction->amount = isset($_POST['amount']) ? (int) trim(str_replace('.', '', $_POST['amount'])) : 0;
    $transaction->remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
    $transaction->date = to_mysql_date(isset($_POST['date']) ? trim((string)$_POST['date']) : '');

    if (empty($transaction->amount))
      $errors['amount'] = 'Jumlah uang harus diisi.';

    if (empty($transaction->remarks))
      $errors['remarks'] = 'Catatan harus diisi.';

    if (empty($errors)) {
      $transaction->userId = $_SESSION['CURRENT_USER']->id;

      $db->beginTransaction();
      if (!$transaction->id) {
        $q = $db->prepare('insert into debts_transactions'
          . ' ( accountId, remarks, amount, date)'
          . ' values'
          . ' (:accountId,:remarks,:amount,:date)'
        );
        $q->bindValue(':accountId', $transaction->accountId);
      }
      else {
        $q = $db->prepare('update debts_transactions set'
          . ' remarks=:remarks, amount=:amount, date=:date'
          . ' where id=' . (int)$transaction->id
        );
      }
      $q->bindValue(':remarks', $transaction->remarks);
      $q->bindValue(':amount', $transaction->amount);
      $q->bindValue(':date', $transaction->date);
      $q->execute();
      $db->commit();

      $_SESSION['FLASH_MESSAGE'] = 'Transaksi utang piutang disimpan.';
      header('Location: ./view?id=' . $transaction->accountId);
      exit;
    }
  }
}

render('finance/debts/transaction-editor', [
  'transaction' => $transaction,
  'accountName' => $accountName,
  'errors' => $errors,
]);
