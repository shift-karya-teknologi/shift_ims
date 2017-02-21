<?php

render('layout', [
  'title' => 'Periksa Voucher',
  'sidenav' => render('shiftnet/sidenav', true),
  'content' => render('shiftnet/voucher/check', true)
]);