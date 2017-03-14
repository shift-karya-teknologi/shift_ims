<?php

$now = new DateTime();
$data = [];

$shiftStart = get_shift_start($now);
$shiftMid = clone $shiftStart;
$shiftMid->add(new DateInterval('PT12H'));
$shiftEnd = get_shift_end($now);

$q = $db->prepare('select sum(totalPrice) from sales_orders where status=1 and (closeDateTime >=? and closeDateTime<?)');
$q->bindValue(1, $shiftStart->format('Y-m-d H:i:s'));
$q->bindValue(2, $shiftMid->format('Y-m-d H:i:s'));
$q->execute();
$data['shiftkom']['total_sales_1'] = $q->fetch(PDO::FETCH_COLUMN);

$q = $db->prepare('select sum(totalPrice) from sales_orders where status=1 and (closeDateTime >=? and closeDateTime<=?)');
$q->bindValue(1, $shiftMid->format('Y-m-d H:i:s'));
$q->bindValue(2, $shiftEnd->format('Y-m-d H:i:s'));
$q->execute();
$data['shiftkom']['total_sales_2'] = $q->fetch(PDO::FETCH_COLUMN);

$q = $db->prepare('select sum(price) from shiftnet_voucher_transactions where (creationDateTime >=? and creationDateTime<?)');
$q->bindValue(1, $shiftStart->format('Y-m-d H:i:s'));
$q->bindValue(2, $shiftMid->format('Y-m-d H:i:s'));
$q->execute();
$data['shiftnet']['total_sales_1'] = $q->fetch(PDO::FETCH_COLUMN);

$q = $db->prepare('select sum(price) from shiftnet_voucher_transactions where (creationDateTime >=? and creationDateTime<=?)');
$q->bindValue(1, $shiftMid->format('Y-m-d H:i:s'));
$q->bindValue(2, $shiftEnd->format('Y-m-d H:i:s'));
$q->execute();
$data['shiftnet']['total_sales_2'] = $q->fetch(PDO::FETCH_COLUMN);

$q = $db->prepare('select sum(amount) from operational_costs where (dateTime >=? and dateTime<?)');
$q->bindValue(1, $shiftStart->format('Y-m-d H:i:s'));
$q->bindValue(2, $shiftMid->format('Y-m-d H:i:s'));
$q->execute();
$data['costs']['shift_1'] = -$q->fetch(PDO::FETCH_COLUMN);

$q = $db->prepare('select sum(amount) from operational_costs where (dateTime >=? and dateTime<?)');
$q->bindValue(1, $shiftMid->format('Y-m-d H:i:s'));
$q->bindValue(2, $shiftEnd->format('Y-m-d H:i:s'));
$q->execute();
$data['costs']['shift_2'] = -$q->fetch(PDO::FETCH_COLUMN);

$data['stock'] = $db->query('select sum(cost*quantity) from products')->fetch(PDO::FETCH_COLUMN);

render('layout', [
  'title'   => 'Dashboard',
  'sidenav' => render('sidenav', true),
  'content' => render('index', [
    'data' => $data,
  ], true),
]);
