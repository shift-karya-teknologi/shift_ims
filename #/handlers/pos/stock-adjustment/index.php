<?php

$filter = [];

$filter['status'] = isset($_GET['status']) ? (int)$_GET['status'] : 0;
$sql = 'select * from stock_adjustments';

if ($filter['status'] >= 0)
  $sql .= ' where status=' . $filter['status'];

$sql .= ' order by dateTime desc';

$items = $db->query($sql)->fetchAll(PDO::FETCH_OBJ);

render('pos/stock-adjustment/list', [
  'items'  => $items,
  'filter' => $filter
]);

