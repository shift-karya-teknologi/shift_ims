<?php

ensure_current_user_can('view-debts-account');

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  $account = $db->query('select * from debts_accounts where id=' . $id)->fetchObject();
  if (!$account) {
    $_SESSION['FLASH_MESSAGE'] = 'Akun tidak ditemukan';
    header('Location: ./');
    exit;
  }
  
  if (!in_array($account->id, get_current_user_finance_account_ids())) {
    exit(render('error/403'));
  }
}

$sql = "SELECT t.*"
  . " FROM debts_transactions t";
  
$where = ["t.accountId=$account->id"];

$where = implode(' and ', $where);
$sql .= " where $where order by t.date desc";

$items = $db->query($sql)->fetchAll(PDO::FETCH_CLASS);

render('finance/debts/view', [
  'account' => $account,
  'items'   => $items,
]);
