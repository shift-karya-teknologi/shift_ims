<?php

require CORELIB_PATH . '/Product.php';

if ($_SESSION['CURRENT_USER']->groupId != 1)
  exit(header('Location: ./'));
  
$startDateTime = new DateTime(date('Y-m-d 00:00:00'));
$endDateTime = clone $startDateTime;
$endDateTime->add(new DateInterval('P1D'));

$q = $db->prepare('select d.*,'
  . ' p.type productType, p.name productName, p.uom uom'
  . ' from sales_order_details d'
  . ' inner join sales_orders o on o.id=d.parentId'
  . ' inner join products p on p.id=d.productId'
  . ' where o.status=1 and (o.closeDateTime>=? and o.closeDateTime<?)'
  . ' order by productName asc'
);

$q->bindValue(1, $startDateTime->format('Y-m-d H:i:s'));
$q->bindValue(2, $endDateTime->format('Y-m-d H:i:s'));
$q->execute();

$items = [];
while ($record = $q->fetchObject()) {  
  if (!isset($data->itemByTypes[$record->productType]))
    $shift->itemByTypes[$record->productType] = [];
  
  if (!isset($items[$record->productType][$record->productName]))
    $items[$record->productType][$record->productName] = [
      'uom' => $record->uom,
      'quantity' => 0,
      'price' => 0,
      'profit' => 0,
    ];
  
  $items[$record->productType][$record->productName]['quantity'] += $record->quantity;
  $items[$record->productType][$record->productName]['price'] += $record->subtotalPrice;
  $items[$record->productType][$record->productName]['profit'] += ($record->subtotalPrice - $record->subtotalCost);
}
render('pos/report/daily-sales-by-type', [
  'dateTime' => $startDateTime->format('Y-m-d H:i:s'),
  'data' => $items
]);