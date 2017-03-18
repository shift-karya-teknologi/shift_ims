<?php

ensure_current_user_can('view-credit-accounts');

require CORELIB_PATH . '/CreditAccount.php';

$sql = 'select a.*, r.name referralName'
  . ' from credit_accounts a'
  . ' inner join credit_referrals r on r.id=a.referralId'
  . ' order by a.id asc';

$q = $db->prepare($sql);
$q->execute();
$items = $q->fetchAll(PDO::FETCH_CLASS, CreditAccount::class);

render('credit/account/list', [
  'items' => $items,
]);