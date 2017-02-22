<?php

$clients = $db->query('select * from shiftnet_clients')->fetchAll(PDO::FETCH_OBJ);

render('layout', [
  'title'   => 'Client Monitor',
  'sidenav' => render('shiftnet/sidenav', true),
  'headnav'  => render('shiftnet/client-monitor/list--menu', true),
  'content' => render('shiftnet/client-monitor/list', [
    'clients' => $clients,
  ], true),
  'scripts' => [
    'files' => ['src' => JS_BASE_URL . 'client-monitor.js'],
  ]
]);