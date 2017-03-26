<?php

if ($_SESSION['CURRENT_USER']->groupId != 1)
  exit(header('Location: ./'));
  

$month = isset($_GET['month']) ? (string)$_GET['month'] : null;

if ($date = check_mysql_date($month . "-01")) {
  $m = $date['month'];
  $y = $date['year'];
}
else {
  $m = date('m');
  $y = date('y');
  $m = str_pad($m, 2, '0', STR_PAD_LEFT);
}

$startDateTime = new DateTime("$y-$m-1 00:00:00");
$endDateTime = clone $startDateTime;
$endDateTime->add(new DateInterval('P1M'));

$q = $db->prepare('select oc.dateTime, oc.description, oc.amount, occ.name categoryName'
  . ' from operational_costs oc'
  . ' inner join operational_cost_categories occ on occ.id=oc.categoryId'
  . ' where (oc.dateTime>=? and oc.dateTime <?)'
  . ' order by oc.dateTime asc');
$q->bindValue(1, $startDateTime->format('Y-m-d H:i:s'));
$q->bindValue(2, $endDateTime->format('Y-m-d H:i:s'));
$q->execute();

$items = [];
for ($i = 1; $i <= $startDateTime->format('t'); $i++) {
  $d = str_pad($i, 2, '0', STR_PAD_LEFT);
  $items["$y-$m-$d"] = [];
}

while ($item = $q->fetchObject()) {
  $items[date('Y-m-d', strtotime($item->dateTime))][] = $item;
}

render('cost/report/monthly-detail', ['items' => $items, 'date' => strtotime($startDateTime->format('Y-m-01'))]);