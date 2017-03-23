<?php

ensure_current_user_can('open-finance-app');

$data = [];

$data['accounts'] = $db->query('select * from multipayment_accounts where active=1 order by name asc')
  ->fetchAll(PDO::FETCH_OBJ);

render('multipayment/index', $data);