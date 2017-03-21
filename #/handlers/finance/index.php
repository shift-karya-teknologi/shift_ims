<?php

ensure_current_user_can('open-finance-app');

$data = [];

if ($_SESSION['CURRENT_USER']->groupId == 1) {
  $data['cash']['accounts'] = $db->query('select * from finance_accounts order by name asc')->fetchAll(PDO::FETCH_OBJ);
  $data['cash']['total'] = $db->query('select sum(balance) from finance_accounts')->fetchColumn();
  $data['credit']['balance'] = $db->query('select sum(balance) from credit_accounts')->fetchColumn();
  $data['credit']['administrationCost'] = $db->query('select sum(administrationCost) from credit_accounts')->fetchColumn();
  $data['debts']['accounts'] = $db->query("select * from debts_accounts where balance<>0 order by name asc")->fetchAll(PDO::FETCH_OBJ);
  $data['debts']['total'] = $db->query('select sum(balance) from debts_accounts')->fetchColumn();
}
else {
  $data['cash']['account'] = $db->query("select * from finance_accounts where id=$cfg[store_account_id]")->fetchObject();
}

render('finance/index', $data);