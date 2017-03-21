<?php

ensure_current_user_can('view-debts-accounts');

$items = $db->query('select * from debts_accounts where balance <> 0 order by name asc')->fetchAll(PDO::FETCH_OBJ);

render('finance/debts/list', [
  'items' => $items,
]);