<?php

class SalesOrderItem {
  public $id;
  public $parentId;
  public $productId;
  public $productName;
  public $quantity = 0;
  public $uom = '';
  public $cost = 0;
  public $price = 0;
  public $subtotalCost = 0;
  public $subtotalPrice = 0;
}

$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$orderId = (int)(isset($_REQUEST['pid']) ? $_REQUEST['pid'] : 0);

if (!$id) {
  $item = new SalesOrderItem();
  $item->parentId = $orderId;
}
else {
  $item = $db->query('select * from sales_order_details where id='.$id)->fetchObject(SalesOrderItem::class);
  $orderId = $item->parentId;
}

$order = $db->query('select * from sales_orders where id=' . $orderId)->fetchObject();
if (!$order || $order->status != 0) {
  header('Location: ./editor?id=' . $orderId);
  exit;
}

$products = [];
$q = $db->query('select id, name from products where active=1 and type <= 200 order by name asc');
while ($r = $q->fetchObject()) {
  $products[] = $r;
  $productByIds[$r->id] = $r;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'];
  if ($action == 'delete') {
    $db->beginTransaction();
    $db->query('delete from sales_order_details where id=' . $id);
    update_sales_order_subtotal($orderId);
    $db->commit();
    
    header('Location: ./editor?id=' . $orderId);
    exit;
  }
  
  $item->productId = (int)$_POST['productId'];
  $item->quantity = str_replace('.', '', (string)$_POST['quantity']);
  $item->price = str_replace('.', '', (string)$_POST['price']);
  $item->cost = $db->query('select cost from products where id=' . $item->productId)->fetch(PDO::FETCH_COLUMN);
  $db->beginTransaction();
  if (!$item->id) {
    $q = $db->prepare('insert into sales_order_details'
      . ' ( parentId, productId, quantity, cost, price, subtotalCost, subtotalPrice)'
      . ' values '
      . ' (:parentId,:productId,:quantity,:cost,:price,:subtotalCost,:subtotalPrice)');
    $q->bindValue(':parentId', $item->parentId);
  }
  else {
    $q = $db->prepare('update sales_order_details set'
      . ' productId=:productId,'
      . ' quantity=:quantity,'
      . ' cost=:cost,'
      . ' price=:price,'
      . ' subtotalCost=:subtotalCost,'
      . ' subtotalPrice=:subtotalPrice'
      . ' where id=:id');
    $q->bindValue(':id', $item->id);
  }
  $q->bindValue(':productId', $item->productId);
  $q->bindValue(':quantity', $item->quantity);
  $q->bindValue(':cost', $item->cost);
  $q->bindValue(':price', $item->price);
  $q->bindValue(':subtotalCost', $item->cost * $item->quantity);
  $q->bindValue(':subtotalPrice', $item->price * $item->quantity);
  $q->execute();
  
  update_sales_order_subtotal($orderId);
  
  $db->commit();
  header('Location: ./editor?id='.$orderId);
  exit;
}

$priceByProductIds = [];
$q = $db->query('select * from product_prices order by productId asc, quantityMin asc');
while ($r = $q->fetchObject()) {
  unset($r->id);
  $priceByProductIds[$r->productId][] = $r;
  unset($r->productId);
}

render('layout', [
  'title'   => $id ? 'Edit Item' : 'Tambah Item',
  'headnav' => '
    <a href="./editor?id=' . $orderId . '" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">close</i>
    </a>'
  ,
  'sidenav' => render('pos/sidenav', true),
  'content' => render('pos/sales-order/item-editor', [
    'item' => $item,
    'products' => $products,
    'priceByProductIds' => $priceByProductIds,
  ], true),
]);
