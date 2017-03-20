<?php

ensure_current_user_can('view-finance-transaction-categories');

$items = [];

$q = $db->query("select *"
  . " from finance_transaction_categories"
  . " where groupId={$_SESSION['CURRENT_USER']->groupId}"
  . " order by name asc");
while ($item = $q->fetchObject()) {
  $items[] = $item;
}

render('finance/transaction-category/list', [
  'items' => $items,
]);
