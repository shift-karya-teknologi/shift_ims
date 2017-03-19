<?php

ensure_current_user_can('open-pos-app');

require CORELIB_PATH . '/Product.php';

class Sales
{
  public $total = 0;
  public $types = [];
}

class SalesByType 
{
  public $total;
  public $items = [];
}

function _get_sales($startDateTime, $endDateTime)
{
  global $db;
  $q = $db->prepare('select d.*,'
    . ' p.type productType, p.name productName, p.uom uom,'
    . ' o.closeDateTime'
    . ' from sales_order_details d'
    . ' inner join sales_orders o on o.id=d.parentId'
    . ' inner join products p on p.id=d.productId'
    . ' where o.status=1 and (o.closeDateTime>=? and o.closeDateTime<?)'
    . ' order by productName asc'
  );
  $q->bindValue(1, $startDateTime);
  $q->bindValue(2, $endDateTime);
  $q->execute();

  $sales = ['Siang' => new Sales(), 'Malam' => new Sales()];

  while ($record = $q->fetchObject()) {
    $dateTime = new DateTime($record->closeDateTime);
    $hour = $dateTime->format('H');
    $shift = $hour >= 6 && $hour < 18 ? 'Siang' : 'Malam';
    $shift = $sales[$shift];

    if (!isset($shift->types[$record->productType]))
      $shift->types[$record->productType] = new SalesByType();

    $itemByType = $shift->types[$record->productType];

    if (!isset($itemByType->items[$record->productId])) {

      $itemByType->items[$record->productId] = [
        'name' => $record->productName,
        'uom' => $record->uom,
        'quantity' => 0,
        'total' => 0,
      ];
    }

    $itemByType->items[$record->productId]['quantity'] += $record->quantity;
    $itemByType->items[$record->productId]['total'] += $record->subtotalPrice;
    $itemByType->total += $record->subtotalPrice;
    $shift->total += $record->subtotalPrice;
  }
  
  return $sales;
}

class Cost {
  public $total = 0;
  public $categories = [];
}

function _get_costs($startDateTime, $endDateTime)
  {
  global $db;
    $q = $db->prepare('select oc.*, occ.name categoryName'
      . ' from operational_costs oc'
      . ' inner join operational_cost_categories occ on occ.id=oc.categoryId'
      . ' where dateTime>=? and dateTime<?'
      . ' order by occ.name asc'
    );
    $q->bindValue(1, $startDateTime);
    $q->bindValue(2, $endDateTime);
    $q->execute();

    $costs = ['Siang' => new Cost(), 'Malam' => new Cost()];

    while ($record = $q->fetchObject()) {
      $dateTime = new DateTime($record->dateTime);
      $hour = $dateTime->format('H');
      $shift = $hour >= 6 && $hour < 18 ? 'Siang' : 'Malam';
      $shift = $costs[$shift];

      if (!isset($shift->categories[$record->categoryId]))
        $shift->categories[$record->categoryId] = ['name' => $record->categoryName, 'total' => 0];

      $shift->categories[$record->categoryId]['total'] += $record->amount;
      $shift->total += $record->amount;
    }

    return $costs;
  }

$date = date('Y-m-d');
$dateTime = new DateTime($date . ' ' . date('H:i:s'));
if ($dateTime->format('H') < 6)
  $dateTime->sub(new DateInterval('P1D'));
$date = $dateTime->format('Y-m-d');

$startDateTime = new DateTime("$date 06:00:00");
$endDateTime = clone $startDateTime;
$endDateTime->add(new DateInterval('P1D'));
$startDateTime = $startDateTime->format('Y-m-d H:i:s');
$endDateTime = $endDateTime->format('Y-m-d H:i:s');

$sales = _get_sales($startDateTime, $endDateTime);
$costs = _get_costs($startDateTime, $endDateTime);

$hour = (int)date('H');
if ($hour >= 19 || $hour < 6) {
  unset($sales['Siang']);
  unset($costs['Siang']);
}
else if ($hour != 18) {
  unset($sales['Malam']);
  unset($costs['Malam']);
}

render('pos/index', [
  'sales' => $sales,
  'costs' => $costs,
]);
