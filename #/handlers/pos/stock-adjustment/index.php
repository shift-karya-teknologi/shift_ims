<?php

if (!isset($_SESSION['STOCK_ADJUSTMENT_MANAGER'])) $_SESSION['STOCK_ADJUSTMENT_MANAGER'] = [];
if (!isset($_SESSION['STOCK_ADJUSTMENT_MANAGER']['status'])) $_SESSION['STOCK_ADJUSTMENT_MANAGER']['status'] = 0;
if (!isset($_SESSION['STOCK_ADJUSTMENT_MANAGER']['lastmod'])) $_SESSION['STOCK_ADJUSTMENT_MANAGER']['lastmod'] = 'anytime';

$filter = [];
$filter['status'] = isset($_GET['status']) ? (int)$_GET['status'] : $_SESSION['STOCK_ADJUSTMENT_MANAGER']['status'];
$filter['lastmod'] = isset($_GET['lastmod']) ? (string)$_GET['lastmod'] : $_SESSION['STOCK_ADJUSTMENT_MANAGER']['lastmod'];

$_SESSION['STOCK_ADJUSTMENT_MANAGER'] = $filter;
  
$sql = 'select * from stock_adjustments';
$where = [];

if ($filter['status'] !== -1) {
  $where[] = 'status=' . $filter['status'];
}

if ($filter['lastmod'] !== 'anytime') {
  $today = new DateTime(date('Y-m-d 00:00:00'));
  $startDateTime = clone $today;
  
  if ($filter['lastmod'] === 'thismonth') {
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
  
  $where[] = "(dateTime>='$startDateTime' and dateTime<='$endDateTime')";

}

$where = implode(' and ', $where);
if (!empty($where))
  $sql .= " where $where";
$sql .= ' order by dateTime desc';

$items = $db->query($sql)->fetchAll(PDO::FETCH_OBJ);

render('pos/stock-adjustment/list', [
  'items'  => $items,
  'filter' => $filter
]);

