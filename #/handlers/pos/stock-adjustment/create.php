<?php

require_once CORELIB_PATH . '/Product.php';

$items = [];
$itemByIds = [];

$q = $db->prepare('select p.*,'
  . ' (select ifnull(max(pp.price1min), 0) from product_prices pp where pp.productId=p.id) price'
  . ' from products p'
  . ' where p.type=0 and p.active=1'
  . ' order by p.name asc');
$q->execute();
while ($item = $q->fetchObject()) {
  $items[] = $item;
  $itemByIds[$item->id] = $item;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['p']) && is_array($_POST['p'])) {
    $productIds = array_keys($_POST['p']);
    $db->beginTransaction();
    $q = $db->prepare('insert into stock_adjustments'
      . ' (dateTime)'
      . ' values'
      . ' (:dateTime)');
    $q->bindValue(':dateTime', date('Y-m-d H:i:s'));
    $q->execute();
    
    $stockAdjustmentId = $db->lastInsertId();
    foreach ($productIds as $productId) {
      $item = $itemByIds[$productId];
      $q = $db->prepare('insert into stock_adjustment_details'
        . ' ( parentId, productId, quantity, cost, price, subtotalCost, subtotalPrice)'
        . ' values'
        . ' (:parentId,:productId,:quantity,:cost,:price,:subtotalCost,:subtotalPrice)');
      $q->bindValue(':parentId', $stockAdjustmentId);
      $q->bindValue(':productId', $productId);
      $q->bindValue(':quantity', $item->quantity);
      $q->bindValue(':cost', $item->cost);
      $q->bindValue(':price', $item->price);
      $q->bindValue(':subtotalCost', $item->cost * $item->quantity);
      $q->bindValue(':subtotalPrice', $item->price * $item->quantity);
      $q->execute();
    }
    $db->commit();
    header('Location: ./editor?id=' . $stockAdjustmentId);
    exit;
  }
}
render('layout', [
  'title'   => 'Pilih Produk',
  'headnav' => '
    <a href="./" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">close</i>
    </a>',
  'sidenav' => render('pos/sidenav', true),
  'content' => render('pos/stock-adjustment/create', [
    'items'  => $items
  ], true),
]);
