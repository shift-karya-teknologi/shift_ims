<?php

$items = [];

$q = $db->query('select * from operational_cost_categories order by name asc');
while ($item = $q->fetchObject()) {
  $items[] = $item;
}

render('finance/operational-cost-category/list', [
  'items' => $items,
]);
