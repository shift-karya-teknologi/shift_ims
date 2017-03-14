<?php

ensure_current_user_can('view-multipayment-accounts');

$items = [];

$q = $db->query('select * from multipayment_accounts order by name asc');
while ($item = $q->fetchObject()) {
  $items[] = $item;
}

render('pos/multipayment-account/list', [
  'items' => $items,
]);
