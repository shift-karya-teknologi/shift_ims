<?php

$clients = $db->query('select id from shiftnet_clients order by id asc')->fetchAll(PDO::FETCH_OBJ);
$groups = [
  1 => 'Administrator',
  2 => 'Member',
  3 => 'Tamu',
];
$str = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
$groupId  = (int)filter_input(INPUT_GET, 'groupId', FILTER_VALIDATE_INT);
$clientId = (int)filter_input(INPUT_GET, 'clientId', FILTER_VALIDATE_INT);
$where = '';

if (!array_key_exists($groupId, $groups))
  $groupId = 0;

if ($groupId != 0)
  $where .= ' and groupId='.$groupId;

if ($clientId)
  $where .= ' and clientId='.$clientId;

if (!preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $str)) {
  $str = 'now';
}
else {
  $str = explode('/', $str);
  $str = date('Y-m-d', strtotime($str[2] . '-' . $str[1] . '-' . $str['0'])) . ' 06:00:00';
}

$datetime      = new DateTime($str);
$startDatetime = get_shift_start($datetime);
$endDatetime   = get_shift_end($datetime);

$q = $db->prepare('select a.*'
  . ' from shiftnet_activities a'
  . ' where (a.dateTime >= :start and a.dateTime <= :end)'
  . $where
  . ' order by a.dateTime desc');
$q->bindValue(':start', $startDatetime->format('Y-m-d H:i:s'));
$q->bindValue(':end'  , $endDatetime->format('Y-m-d H:i:s'));
$q->execute();

$activities = $q->fetchAll(PDO::FETCH_OBJ);
render('layout', [
  'title'   => 'Aktifitas',
  'sidenav' => render('shiftnet/sidenav', true),
  'content' => render('shiftnet/activity/list', [
    'datetime'   => $datetime,
    'activities' => $activities,
    'groups' => $groups,
    'groupId' => $groupId,
    'clients' => $clients,
    'clientId' => $clientId,
  ], true)
]);