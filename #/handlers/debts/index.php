<?php

ensure_current_user_can('open-debts-app');

$data = [
  'debts'   => [
    'total'    => 0,
    'personal' => [0,0],
    'company'  => [0,0]
  ],
  'credits' => [
    'total'    => 0,
    'personal' => [0,0],
    'company'  => [0,0]
  ],
  'balance' => 0
];

$q = $db->query('select * from debts_accounts where balance <> 0');
while ($r = $q->fetchObject()) {
  $a = $r->balance > 0 ? 'debts' : 'credits';
  $b = $r->type == 0 ? 'personal' : 'company';
  
  $data[$a]['total'] += $r->balance;
  $data[$a][$b][0] += $r->balance;
  $data['balance'] += $r->balance;
}

if ($data['debts']['total'] != 0) {
  $data['debts']['company'][1]  = ($data['debts']['company'][0] / $data['debts']['total']) * 100;
  $data['debts']['personal'][1] = ($data['debts']['personal'][0] / $data['debts']['total']) * 100;
}

if ($data['credits']['total'] != 0) {
  $data['credits']['company'][1]  = abs($data['credits']['company'][0] / $data['credits']['total']) * 100;
  $data['credits']['personal'][1] = abs($data['credits']['personal'][0] / $data['credits']['total']) * 100;
}

render('debts/index', $data);