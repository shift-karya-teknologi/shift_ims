<?php

ensure_current_user_can('open-credit-app');

$total = $db->query('select sum(balance) from credit_accounts')->fetchColumn();

render('credit/index', [
  'total' => $total
]);