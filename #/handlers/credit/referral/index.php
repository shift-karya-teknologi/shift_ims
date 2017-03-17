<?php

ensure_current_user_can('view-credit-referrals');

$sql = 'select r.*,'
  . ' (select count(0) from credit_accounts a where a.balance<>0 and a.referralId=r.id) customerCount'
  . ' from credit_referrals r'
  . ' order by r.name asc';

$q = $db->prepare($sql);
$q->execute();
$items = $q->fetchAll(PDO::FETCH_OBJ);

render('credit/referral/list', [
  'items' => $items,
]);