<?php

ensure_current_user_can('view-multipayment-transactions');

require CORELIB_PATH . '/MultiPaymentTransaction.php';

if (!isset($_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER'])) $_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER'] = [];
if (!isset($_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER']['type'])) $_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER']['type'] = -1;
if (!isset($_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER']['accountId'])) $_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER']['accountId'] = -1;
if (!isset($_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER']['when'])) $_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER']['when'] = 'today';

$filter = [];
$filter['type'] = isset($_GET['type']) ? (int)$_GET['type'] : $_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER']['type'];
$filter['accountId'] = isset($_GET['accountId']) ? (int)$_GET['accountId'] : $_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER']['accountId'];
$filter['when'] = isset($_GET['when']) ? (string)$_GET['when'] : $_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER']['when'];

$_SESSION['MULTIPAYMENT_TRANSACTION_MANAGER'] = $filter;

$sql  = 'select t.*, a.name accountName, u.username username'
      . ' from multipayment_transactions t'
      . ' inner join multipayment_accounts a on a.id=t.accountId'
      . ' inner join users u on u.id=t.userId';

$where = [];

if ($filter['type'] !== -1) {
  $where[] = 't.type=' . $filter['type'];
}

if ($filter['accountId'] !== -1) {
  $where[] = 't.accountId=' . $filter['accountId'];
}

if ($filter['when'] !== 'anytime') {
  $today = new DateTime(date('Y-m-d 00:00:00'));
  $startDateTime = clone $today;
  
  if ($filter['when'] === 'today') {
    $dayNum = $today->format('N');
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1D'));
    $endDateTime->sub(new DateInterval('PT1S'));
  }
  else if ($filter['when'] === 'yesterday') {
    $dayNum = $today->format('N');
    $startDateTime->sub(new DateInterval('P1D'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1D'));
    $endDateTime->sub(new DateInterval('PT1S'));
  }
  else if ($filter['when'] === 'thisweek') {
    $dayNum = $today->format('N');
    $startDateTime->sub(new DateInterval('P' . ($dayNum - 1) . 'D'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P6D'));
  }
  else if ($filter['when'] === 'prevweek') {
    $dayNum = $today->format('N');
    $startDateTime->sub(new DateInterval('P' . (7 + $dayNum - 1) . 'D'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P6D'));
  }
  else if ($filter['when'] === 'thismonth') {
    $startDateTime = new DateTime($today->format('Y-m-01 00:00:00'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1M'));
    $endDateTime->sub(new DateInterval('P1D'));
  }
  else if ($filter['when'] === 'prevmonth') {
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
  
  $startDateTime = $startDateTime->format('Y-m-d 00:00:00');
  $endDateTime = $endDateTime->format('Y-m-d 23:59:59');
  
  $where[] = "(t.dateTime>='$startDateTime' and t.dateTime<='$endDateTime')";
}

$where = implode(' and ', $where);
if (!empty($where)) $sql .= " where $where";

$sql .= ' order by t.dateTime desc';
$items = [];
$q = $db->query($sql);
while ($item = $q->fetchObject(MultiPaymentTransaction::class))
  $items[] = $item;

$types = MultiPaymentTransaction::getTypes();
$accounts = $db->query('select id, name from multipayment_accounts order by name asc')->fetchAll(PDO::FETCH_OBJ);

render('pos/multipayment-transaction/list', [
  'items' => $items,
  'filter' => $filter,
  'accounts' => $accounts,
  'types' => $types,
]);
