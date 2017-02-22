<?php

$voucher = null;
$error   = false;
$code    = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING); 

if ($code === null) {
  // ignore
}
else if ($code === '') {
  $error = 'Masukkan 6 digit kode voucher yang akan diperiksa.';
}
else if (strlen($code) < 6) {
  $error = 'Kode voucher yang valid adalah 6 digit.';
}
else {  
  $q = $db->prepare(''
    . 'select'
    . ' a.code, a.remainingDuration, a.activeClientId, a.lastActiveUsername,'
    . ' t.price, t.duration, t.operatorId, t.creationDateTime, t.expirationDateTime,'
    . ' u.username as creationOperator'
    . ' from shiftnet_active_vouchers a'
    . ' inner join shiftnet_voucher_transactions t on t.id = a.voucherId'
    . ' inner join users u on u.id = t.operatorId'
    . ' where a.code=?');
  $q->bindValue(1, $code);
  $q->execute();
  
  $voucher = $q->fetch(PDO::FETCH_OBJ);

  if (!$voucher) {
    $error = 'Kode voucher tidak ditemukan.';
  }
  else {
    $voucher->status = 'Belum dipakai';
    if ($voucher->activeClientId) {
      if ($voucher->lastActiveUsername) {
        $voucher->status = 'Sedang dipakai ' . $voucher->lastActiveUsername . ' di client ' . $voucher->activeClientId . '.';
      }
      else {
        $voucher->status = 'Sedang dipakai client ' . $voucher->activeClientId . '.';
      }
    }
    else if ($voucher->lastActiveUsername) {
      $voucher->status = 'Terakhir kali dipakai oleh ' . $voucher->lastActiveUsername . '.';
    }
    else if ((new DateTime($voucher->expirationDateTime)) < (new DateTime('now'))) {
      $voucher->status = 'Sudah Kadaluarsa';
    }
  }
}

render('layout', [
  'title' => 'Periksa Voucher',
  'sidenav' => render('shiftnet/sidenav', true),
  'headnav' => '
    <a href="./" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">close</i>
  </a>',
  'content' => render('shiftnet/voucher/check', [
    'error'   => $error,
    'code'    => $code,
    'voucher' => $voucher,
  ], true)
]);