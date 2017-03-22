<?php

ensure_current_user_can('view-debts-account');

$accountId = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : 0;

if ($accountId) {
  $account = $db->query('select * from debts_accounts where id=' . $accountId)->fetchObject();
  if (!$account) {
    $_SESSION['FLASH_MESSAGE'] = 'Akun tidak ditemukan';
    header('Location: ./');
    exit;
  }
}

$sql = "SELECT t.*"
  . " FROM debts_transactions t";
  
$where = ["t.accountId=$account->id"];

$where = implode(' and ', $where);
$sql .= " where $where order by t.dateTime desc";

$items = $db->query($sql)->fetchAll(PDO::FETCH_CLASS);

render('debts/transaction/index', [
  'account' => $account,
  'items'   => $items,
]);
