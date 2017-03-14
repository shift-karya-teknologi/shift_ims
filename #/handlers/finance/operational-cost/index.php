<?php

if (!isset($_SESSION['OPERATIONAL_COST_MANAGER'])) $_SESSION['OPERATIONAL_COST_MANAGER'] = [];
if (!isset($_SESSION['OPERATIONAL_COST_MANAGER']['date'])) $_SESSION['OPERATIONAL_COST_MANAGER']['date'] = 'today';
if (!isset($_SESSION['OPERATIONAL_COST_MANAGER']['categoryId'])) $_SESSION['OPERATIONAL_COST_MANAGER']['categoryId'] = -1;

$filter = [];
$filter['date'] = isset($_GET['date']) ? (string)$_GET['date'] : $_SESSION['OPERATIONAL_COST_MANAGER']['date'];
$filter['categoryId'] = isset($_GET['categoryId']) ? (int)$_GET['categoryId'] : $_SESSION['OPERATIONAL_COST_MANAGER']['categoryId'];

$_SESSION['OPERATIONAL_COST_MANAGER'] = $filter;

$sql = 'select o.*, c.name categoryName'
  . ' from operational_costs o'
  . ' inner join operational_cost_categories c on c.id = o.categoryId';
$where = [];

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
  
  $where[] = "(o.dateTime>='$startDateTime' and o.dateTime<='$endDateTime')";
}

if ($filter['categoryId'] !== -1) {
  $where[] = "categoryId={$filter['categoryId']}";
}

$where = implode(' and ', $where);
if (!empty($where))
  $sql .= " where $where";

$sql .= ' order by o.dateTime desc';

$items = $db->query($sql)->fetchAll(PDO::FETCH_OBJ);
$categories = $db->query('select * from operational_cost_categories')->fetchAll(PDO::FETCH_OBJ);
render('finance/operational-cost/list', [
  'items' => $items,
  'filter' => $filter,
  'categories' => $categories,
]);
