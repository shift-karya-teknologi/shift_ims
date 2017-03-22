<?php

ensure_current_user_can('open-finance-app');

$data = [];

if ($_SESSION['CURRENT_USER']->groupId == 1) {
  $data['accounts'] = $db->query('select * from finance_accounts order by name asc')
    ->fetchAll(PDO::FETCH_OBJ);
}
else {
  $data['accounts'] = $db->query("select * from finance_accounts"
    . " where id in('" . implode("','", get_current_user_finance_account_ids()) . "')")
    ->fetchAll(PDO::FETCH_OBJ);
}

render('finance/index', $data);