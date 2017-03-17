<?php

ensure_current_user_can('view-credit-accounts');

require CORELIB_PATH . '/CreditAccount.php';

$sql = 'select * from credit_accounts order by id asc';

$q = $db->prepare($sql);
$q->execute();
$items = $q->fetchAll(PDO::FETCH_CLASS, CreditAccount::class);

render('credit/account/list', [
  'items' => $items,
]);