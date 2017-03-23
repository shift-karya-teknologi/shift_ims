<?php

ensure_current_user_can('view-finance-account');

require CORELIB_PATH . '/FinanceAccount.php';
require CORELIB_PATH . '/FinanceTransaction.php';

$id = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : 0;

if ($id) {
  $account = $db->query('select * from finance_accounts where id=' . $id)->fetchObject(FinanceAccount::class);
  if (!$account) {
    $_SESSION['FLASH_MESSAGE'] = 'Akun tidak ditemukan';
    header('Location: ../account/');
    exit;
  }
  
  if (!in_array($account->id, get_current_user_finance_account_ids())) {
    exit(render('error/403'));
  }
}

if (!isset($_SESSION['FINANCE_TRANSACTION_MANAGER'])) $_SESSION['FINANCE_TRANSACTION_MANAGER'] = [];
if (!isset($_SESSION['FINANCE_TRANSACTION_MANAGER']['date'])) $_SESSION['FINANCE_TRANSACTION_MANAGER']['date'] = 'today';

$filter = [];
$filter['date'] = isset($_GET['date']) ? (string)$_GET['date'] : $_SESSION['FINANCE_TRANSACTION_MANAGER']['date'];

$_SESSION['FINANCE_TRANSACTION_MANAGER'] = $filter;

$sql = "SELECT t.*"
  . " FROM finance_transactions t";
  
$where = ["t.accountId=$account->id"];
$endDateTime = null;

if ($filter['date'] !== 'anytime') {
  $today = new DateTime(date('Y-m-d 00:00:00'));
  $startDateTime = clone $today;
  
  if ($filter['date'] === 'today') {
    $dayNum = $today->format('N');
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1D'));
    $endDateTime->sub(new DateInterval('PT1S'));
  }
  else if ($filter['date'] === 'yesterday') {
    $dayNum = $today->format('N');
    $startDateTime->sub(new DateInterval('P1D'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1D'));
    $endDateTime->sub(new DateInterval('PT1S'));
  }
  else if ($filter['date'] === 'thisweek') {
    $dayNum = $today->format('N');
    $startDateTime->sub(new DateInterval('P' . ($dayNum - 1) . 'D'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P6D'));
  }
  else if ($filter['date'] === 'prevweek') {
    $dayNum = $today->format('N');
    $startDateTime->sub(new DateInterval('P' . (7 + $dayNum - 1) . 'D'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P6D'));
  }
  else if ($filter['date'] === 'thismonth') {
    $startDateTime = new DateTime($today->format('Y-m-01 00:00:00'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1M'));
    $endDateTime->sub(new DateInterval('P1D'));
  }
  else if ($filter['date'] === 'prevmonth') {
    $startDateTime = new DateTime($today->format('Y-m-01 00:00:00'));
    $startDateTime->sub(new DateInterval('P1M'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1M'));
    $endDateTime->sub(new DateInterval('P1D'));
  }
  else {
    // bad request
    header('Location: ?');
    exit;
  }
  
  $startDateTime = $startDateTime->format('Y-m-d H:i:s');
  $endDateTime = $endDateTime->format('Y-m-d H:i:s');
  
  $where[] = "(t.dateTime>='$startDateTime' and t.dateTime<='$endDateTime')";
}

$where = implode(' and ', $where);
$sql .= " where $where order by t.dateTime desc";

$items = $db->query($sql)->fetchAll(PDO::FETCH_CLASS, FinanceTransaction::class);

$q = $db->query("select sum(amount)"
  . " from finance_transactions"
  . " where accountId=$account->id"
  . " and dateTime<='$endDateTime'");
$account->lastBalance = $q->fetchColumn();

render('finance/transaction/index', [
  'account' => $account,
  'items'   => $items,
  'filter'  => $filter,
]);
