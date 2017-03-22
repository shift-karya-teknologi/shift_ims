<?php

ensure_current_user_can('open-finance-app');

$data = [];

if ($_SESSION['CURRENT_USER']->groupId == 1) {
  $data['accounts'] = $db->query('select * from finance_accounts order by name asc')
    ->fetchAll(PDO::FETCH_OBJ);
}
else {

}

render('finance/index', $data);