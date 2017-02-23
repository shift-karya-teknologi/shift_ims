<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if (!($id && $member = $db->query("select * from shiftnet_members where id=$id")->fetch(PDO::FETCH_OBJ))) {
  header('Location: ./');
  exit;
}

$datetime = new DateTime();
$datetime->sub(new DateInterval('P3D'));
$datetime = $datetime->format('Y-m-d H:i:s');

$activities = $db->query("select *"
  . " from shiftnet_activities"
  . " where memberId=$id and dateTime >= '$datetime'")
  ->fetchAll(PDO::FETCH_OBJ);
  
render('layout', [
  'title'    => 'Rincian Member',
  'sidenav'  => render('shiftnet/sidenav', true),
  'headnav'  => render('shiftnet/member/view--menu', ['id' => $member->id ], true),
  'content'  => render('shiftnet/member/view', [
    'member'     => $member,
    'activities' => $activities,
  ], true)
]);