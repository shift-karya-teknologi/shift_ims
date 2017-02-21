<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if (!($id && $member = $db->query("select id, username, active from shiftnet_members where id=$id")
    ->fetch(PDO::FETCH_OBJ))) {
  header('Location: ./');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $active = isset($_POST['active']) ? (int)$_POST['active'] : 0;
  
  $db->beginTransaction();
  $q = $db->prepare('update shiftnet_members set active=? where id=?');
  $q->bindValue(1, $active);
  $q->bindValue(2, $member->id);
  $q->execute();

  if ($q->rowCount() > 0) {
    $q = $db->prepare('update shiftnet_members set lastModifiedOperatorId=?, lastModifiedDateTime=? where id=?');
    $q->bindValue(1, $_SESSION['CURRENT_USER']->id);
    $q->bindValue(2, date('Y-m-d H:i:s'));
    $q->bindValue(3, $member->id);
    $q->execute();
  }
  $db->commit();

  $_SESSION['FLASH_MESSAGE'] = 'Akun member telah diperbarui.';
  header('Location: ./view?id=' . $member->id);
  exit;
}

render('layout', [
  'title'   => 'Perbarui Member',
  'sidenav' => render('shiftnet/sidenav', true),
  'content' => render('shiftnet/member/edit', [
    'member' => $member
  ], true)
]);