<?php


if (!isset($_SESSION['SALES_ORDER_MANAGER'])) $_SESSION['SALES_ORDER_MANAGER'] = [];
if (!isset($_SESSION['SALES_ORDER_MANAGER']['status'])) $_SESSION['SALES_ORDER_MANAGER']['status'] = 0;
if (!isset($_SESSION['SALES_ORDER_MANAGER']['lastmod'])) $_SESSION['SALES_ORDER_MANAGER']['lastmod'] = 'today';

$filter = [];
$filter['status'] = isset($_GET['status']) ? (int)$_GET['status'] : $_SESSION['SALES_ORDER_MANAGER']['status'];
$filter['lastmod'] = isset($_GET['lastmod']) ? (string)$_GET['lastmod'] : $_SESSION['SALES_ORDER_MANAGER']['lastmod'];

$_SESSION['SALES_ORDER_MANAGER'] = $filter;

$sql = 'select so.*, u1.username openUsername, u2.username lastModUsername'
  . ' from sales_orders so';
$where = [];

if ($filter['status'] !== -1) {
  $where[] = 'so.status=' . $filter['status'];
}

if ($filter['lastmod'] !== 'anytime') {
  $today = new DateTime(date('Y-m-d 00:00:00'));
  $startDateTime = clone $today;
  
  if ($filter['lastmod'] === 'today') {
    $dayNum = $today->format('N');
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1D'));
    $endDateTime->sub(new DateInterval('PT1S'));
  }
  else if ($filter['lastmod'] === 'yesterday') {
    $dayNum = $today->format('N');
    $startDateTime->sub(new DateInterval('P1D'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1D'));
    $endDateTime->sub(new DateInterval('PT1S'));
  }
  else if ($filter['lastmod'] === 'thisweek') {
    $dayNum = $today->format('N');
    $startDateTime->sub(new DateInterval('P' . ($dayNum - 1) . 'D'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P6D'));
  }
  else if ($filter['lastmod'] === 'prevweek') {
    $dayNum = $today->format('N');
    $startDateTime->sub(new DateInterval('P' . (7 + $dayNum - 1) . 'D'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P6D'));
  }
  else if ($filter['lastmod'] === 'thismonth') {
    $startDateTime = new DateTime($today->format('Y-m-01 00:00:00'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1M'));
    $endDateTime->sub(new DateInterval('P1D'));
  }
  else if ($filter['lastmod'] === 'prevmonth') {
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
  
  $where[] = "(so.lastModDateTime>='$startDateTime' and so.lastModDateTime<='$endDateTime')";
}

$sql .= ' inner join users u1 on u1.id=so.openUserId';
$sql .= ' inner join users u2 on u2.id=so.lastModUserId';      

$where = implode(' and ', $where);
if (!empty($where)) $sql .= " where $where";

$sql .= ' order by so.lastModDateTime desc';

$items = $db->query($sql)->fetchAll(PDO::FETCH_OBJ);

render('pos/sales-order/list', [
    'filter' => $filter,
    'items'  => $items,
]);
