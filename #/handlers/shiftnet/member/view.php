<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if (!($id && $member = $db->query("select * from shiftnet_members where id=$id")->fetch(PDO::FETCH_OBJ))) {
  header('Location: ./');
  exit;
}
  
render('layout', [
  'title'    => 'Rincian Member',
  'sidenav'  => render('shiftnet/sidenav', true),
  'headnav'  => render('shiftnet/member/view--menu', ['id' => $member->id ], true),
  'content'  => render('shiftnet/member/view', [
    'member' => $member
  ], true)
]);