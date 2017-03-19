<?php

ensure_current_user_can('view-finance-accounts');

$items = get_current_user_finance_accounts();

render('finance/account/list', [
  'items' => $items,
]);