<?php

$str = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
$datetime = null;
if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $str))
  $str = 'now';
else
  $str .= ' 06:00:00';

$datetime = new DateTime($str);
$startDatetime = get_shift_start($datetime);
$endDatetime = get_shift_end($datetime);

$q = $db->prepare('select * from shiftnet_voucher_transactions where (creationDateTime >= :start and creationDateTime <= :end) order by id desc');
$q->bindValue(':start', $startDatetime->format('Y-m-d H:i:s'));
$q->bindValue(':end',   $endDatetime->format('Y-m-d H:i:s'));
$q->execute();

$vouchers = $q->fetchAll(PDO::FETCH_OBJ);

$q = $db->prepare('select sum(price) from shiftnet_voucher_transactions where (creationDateTime >= :start and creationDateTime <= :end) order by creationDateTime desc');
$q->bindValue(':start', get_shift_start($datetime)->format('Y-m-d H:i:s'));
$q->bindValue(':end',   get_shift_end($datetime)->format('Y-m-d H:i:s'));
$q->execute();
$total = $q->fetchColumn(0);

render('layout', [
  'title'   => 'Voucher',
  'sidenav' => render('shiftnet/sidenav', true),
  'headnav' => render('shiftnet/voucher/list--headnav', true),
  'content' => render('shiftnet/voucher/list', [
    'datetime' => $datetime,
    'vouchers' => $vouchers,
    'total'    => $total,
  ], true)
]);