<?php

ensure_current_user_can('view-finance-accounts');

$items = [];

$sql = 'select a.* from finance_accounts a';
if ($_SESSION['CURRENT_USER']->groupId != 1) {
  $ids = get_current_finance_account_ids();
  
  if (empty($ids)) {
    render('finance/account/list', [
      'items' => $items,
    ]);
    exit;
  }
  
  $sql .= ' where id in (' . implode(',', $ids) . ')';
}

$sql .= ' order by a.name asc';
$q = $db->query($sql);

while ($item = $q->fetchObject()) {
  $items[] = $item;
}

render('finance/account/list', [
  'items' => $items,
]);