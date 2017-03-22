<?php

ensure_current_user_can('open-debts-app');

$data = [
  'debts'   => [
    'total'   => 0,
    'items' => [
      'personal' => (float)$db->query('select v.b from (select ifnull(sum(balance),0) b from debts_accounts where type=0) v where b>0')->fetchColumn(),
      'company'  => (float)$db->query('select v.b from (select ifnull(sum(balance),0) b from debts_accounts where type=1) v where b>0')->fetchColumn(),
      'personalPercent' => 0,
      'companyPercent'  => 0,
    ]
  ],
  'credits' => [
    'total' => 0,
    'items' => [
      'personal' => (float)$db->query('select v.b from (select ifnull(sum(balance),0) b from debts_accounts where type=0) v where b<0')->fetchColumn(),
      'company'  => (float)$db->query('select v.b from (select ifnull(sum(balance),0) b from debts_accounts where type=1) v where b<0')->fetchColumn(),
      'personalPercent' => 0,
      'companyPercent'  => 0,
    ]
  ],
  'balance' => 0
];

$data['debts']['total']   = $data['debts']['items']['company'] + $data['debts']['items']['personal'];
$data['credits']['total'] = $data['credits']['items']['company'] + $data['credits']['items']['personal'];
$data['balance'] = $data['debts']['total'] + $data['credits']['total'];

if ($data['debts']['total'] != 0) {
  $data['debts']['items']['companyPercent']  = ($data['debts']['items']['company']  / $data['debts']['total']) * 100;
  $data['debts']['items']['personalPercent'] = ($data['debts']['items']['personal'] / $data['debts']['total']) * 100;
}

if ($data['credits']['total'] != 0) {
  $data['credits']['items']['companyPercent']  = abs($data['credits']['items']['company']  / $data['credits']['total']) * 100;
  $data['credits']['items']['personalPercent'] = abs($data['credits']['items']['personal'] / $data['credits']['total']) * 100;
}

render('debts/index', $data);