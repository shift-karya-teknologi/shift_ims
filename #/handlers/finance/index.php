<?php

render('layout', [
  'title'   => 'Dasbor Keuangan',
  'sidenav' => render('finance/sidenav', true),
  'content' => render('finance/home', [

  ], true),
]);
