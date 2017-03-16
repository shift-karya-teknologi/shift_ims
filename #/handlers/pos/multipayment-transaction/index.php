<?php

ensure_current_user_can('view-multipayment-transactions');

$items = [];

$q = $db->query('select t.*, a.name accountName, u.username username'
  . ' from multipayment_transactions t'
  . ' inner join multipayment_accounts a on a.id=t.accountId'
  . ' inner join users u on u.id = t.userId'
  . ' order by t.dateTime desc'
  );
while ($item = $q->fetchObject()) {
  $items[] = $item;
}

render('pos/multipayment-transaction/list', [
  'items' => $items,
]);
