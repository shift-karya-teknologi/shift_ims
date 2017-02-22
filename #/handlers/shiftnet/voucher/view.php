<?php

function find_voucher_by_id($id) {
  global $db;
  $q = $db->prepare('select * from shiftnet_voucher_transactions where id=?');
  $q->bindValue(1, $id);
  $q->execute();
  return $q->fetch(PDO::FETCH_OBJ);
}

$voucher = null;
if ($id = (int)filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING))
  $voucher = find_voucher_by_id($id);

if (!$voucher) {
  $_SESSION['FLASH_MESSAGE'] = 'Voucher tidak ditemukan.';
  header('Location: ./');
  exit;
}

render('layout', [
  'title'   => 'Rincian Voucher',
  'sidenav' => render('shiftnet/sidenav', true),
  'headnav' => '
    <a href="./" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">close</i>
    </a>',
  'content' => render('shiftnet/voucher/view', [
    'voucher' => $voucher,
  ], true)
]);