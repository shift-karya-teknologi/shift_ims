<?php

ensure_current_user_can('view-operational-cost-categories');

$items = [];

$q = $db->query('select * from operational_cost_categories order by name asc');
while ($item = $q->fetchObject()) {
  $items[] = $item;
}

render('operational-cost/category/list', [
  'items' => $items,
]);
