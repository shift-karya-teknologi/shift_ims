<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if (!($id && $member = $db->query("select id, username, remainingDuration from shiftnet_members where id=$id")
    ->fetch(PDO::FETCH_OBJ))) {
  header('Location: ./');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $db->query('delete from shiftnet_members where id=' . $member->id);
  $_SESSION['FLASH_MESSAGE'] = 'Akun member ' . $member->username . ' telah dihapus permanen dari daftar member.';
  header('Location: ./');
  exit;
}

render('layout', [
  'title'    => 'Hapus Member',
  'sidenav'  => render('shiftnet/sidenav', true),
  'content' => render('shiftnet/member/delete', [
    'member' => $member
  ], true)
]);