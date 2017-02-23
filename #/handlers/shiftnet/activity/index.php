<?php

$str = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);

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
  ], true)
]);