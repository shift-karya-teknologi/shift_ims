<?php

if (!isset($_SESSION['PURCHASING_ORDER_MANAGER'])) $_SESSION['PURCHASING_ORDER_MANAGER'] = [];
if (!isset($_SESSION['PURCHASING_ORDER_MANAGER']['status'])) $_SESSION['PURCHASING_ORDER_MANAGER']['status'] = -1;
if (!isset($_SESSION['PURCHASING_ORDER_MANAGER']['lastmod'])) $_SESSION['PURCHASING_ORDER_MANAGER']['lastmod'] = 'thisweek';

$filter = [];
$filter['status'] = isset($_GET['status']) ? (int)$_GET['status'] : $_SESSION['PURCHASING_ORDER_MANAGER']['status'];
$filter['lastmod'] = isset($_GET['lastmod']) ? (string)$_GET['lastmod'] : $_SESSION['PURCHASING_ORDER_MANAGER']['lastmod'];

$_SESSION['PURCHASING_ORDER_MANAGER'] = $filter;

$sql = 'select * from purchasing_orders';
$where = [];

if ($filter['status'] !== -1) {
  $where[] = 'status=' . $filter['status'];
}

if ($filter['lastmod'] !== 'anytime') {
  $today = new DateTime(date('Y-m-d 00:00:00'));
  $startDateTime = clone $today;
  
  if ($filter['lastmod'] === 'thisweek') {
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
  else if ($filter['lastmod'] === 'thisyear') {
    $startDateTime = new DateTime($today->format('Y-01-01 00:00:00'));
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('P1Y'));
    $endDateTime->sub(new DateInterval('P1D'));
  }
  else {
    // bad request
    header('Location: ?');
    exit;
  }
  
  $startDateTime = $startDateTime->format('Y-m-d 00:00:00');
  $endDateTime = $endDateTime->format('Y-m-d 23:59:59');
  
  $where[] = "(lastModDateTime>='$startDateTime' and lastModDateTime<='$endDateTime')";

}

$where = implode(' and ', $where);
if (!empty($where))
  $sql .= " where $where";
$sql .= ' order by lastModDateTime desc';

$items = $db->query($sql)->fetchAll(PDO::FETCH_OBJ);

render('pos/purchasing-order/list', [
    'filter' => $filter,
    'items'  => $items,
]);
