<?php

ensure_current_user_can('add-stock-adjustment');

require_once CORELIB_PATH . '/Product.php';

$filter['categoryId'] = isset($_GET['categoryId']) ? (int)$_GET['categoryId'] : -1;
$items = [];
$itemByIds = [];

$sql = 'select p.*,'
  . ' (select ifnull(max(pp.price1min), 0) from product_prices pp where pp.productId=p.id) price'
  . ' from products p'
  . ' where p.type=0 and p.active=1';

if ($filter['categoryId'] !== -1) {
  if ($filter['categoryId'] == 0)
    $sql .= ' and categoryId is null';
  else
    $sql .= ' and categoryId='. $filter['categoryId'];
}

$sql .= ' order by p.name asc';
$q = $db->prepare($sql);
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
  
  header('Location: ?');
  exit;
}

$categories = $db->query('select * from product_categories order by name asc')->fetchAll(PDO::FETCH_OBJ);

render('pos/stock-adjustment/create', [
  'items' => $items,
  'filter' => $filter,
  'categories' => $categories,
]);
