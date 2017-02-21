<?php

render('layout', [
  'title' => 'Buat Voucher',
  'sidenav' => render('shiftnet/sidenav', true),
  'content' => render('shiftnet/voucher/create', true)
]);