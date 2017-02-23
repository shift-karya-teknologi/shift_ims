<?php

render('layout', [
  'title'   => 'Dasbor POS',
  'sidenav' => render('pos/sidenav', true),
  'content' => render('pos/home', [
  ], true),
]);
