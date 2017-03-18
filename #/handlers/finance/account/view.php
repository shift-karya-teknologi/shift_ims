<?php

ensure_current_user_can('view-finance-account');

require CORELIB_PATH . '/FinanceAccount.php';
require CORELIB_PATH . '/FinanceTransaction.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  $account = $db->query('select * from finance_accounts where id=' . $id)->fetchObject(FinanceAccount::class);
  if (!$account) {
    $_SESSION['FLASH_MESSAGE'] = 'Akun tidak ditemukan';
    header('Location: ./');
    exit;
  }
  
  if (!in_array($account->id, get_current_finance_account_ids())) {
    exit(render('error/403'));
  }
}

$now = new DateTime();
$start = get_shift_start($now)->format('Y-m-d H:i:s');
$end = get_shift_end($now)->format('Y-m-d H:i:s');

$q = $db->query("select sum(amount)"
  . " from finance_transactions"
  . " where accountId=$account->id"
  . " and dateTime<='$end'");
$account->lastBalance = $q->fetchColumn();


$q = $db->prepare("SELECT t.*"
  . " FROM finance_transactions t"
  . " WHERE t.accountId=$account->id"
  . " and (t.dateTime>=? and t.dateTime<?)"
  . " ORDER BY t.dateTime DESC"
);
$q->bindValue(1, $start);
$q->bindValue(2, $end);
$q->execute();

$items = $q->fetchAll(PDO::FETCH_CLASS, FinanceTransaction::class);

render('finance/account/view', [
  'account' => $account,
  'items'   => $items,
]);
