<?php

if ($_SESSION['CURRENT_USER']->groupId != 1)
  exit(header('Location: ./'));
  
render('report/layout', [
  'title'   => 'Laporan Penjualan',
  'sidenav' => render('report/sidenav', true),
  'content' => render('report/sales/index', true),
]);