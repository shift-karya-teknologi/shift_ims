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
if (!$id) {
  $item = new SalesOrderItem();
  $item->parentId = (int)(isset($_REQUEST['orderId']) ? $_REQUEST['orderId'] : 0);
}
else {
  $item = $db->query('select'
    . ' d.*, p.name productName'
    . ' from sales_order_details d'
    . ' inner join products p on p.id = d.productId'
    . ' where d.id='.$id
    )->fetchObject(SalesOrderItem::class);
}

$order = $db->query('select * from sales_orders where id=' . $item->parentId)->fetchObject();
if (!$order || $order->status != 0) {
  header('Location: ./editor?id=' . $item->parentId);
  exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'];
  if ($action == 'delete') {
    $db->beginTransaction();
    $db->query('delete from sales_order_details where id=' . $id);
    update_sales_order_subtotal($item->parentId);
    update_sales_order_lastmod($item->parentId);
    $db->commit();
    
    header('Location: ./editor?id=' . $item->parentId);
    exit;
  }
  
  $item->productId = (int)$_POST['productId'];
  $item->quantity = str_replace('.', '', (string)$_POST['quantity']);
  $item->price = str_replace('.', '', (string)$_POST['price']);
  $item->cost = $db->query('select cost from products where id=' . $item->productId)->fetch(PDO::FETCH_COLUMN);
  
  if (!$item->productId) {
    $item->productName = '';
    $errors['productId'] = 'Silahkan pilih produk.';
  }
  
  if ($item->quantity <= 0) {
    $errors['quantity'] = 'Kwantitas harus diisi.';
  }
  
  if (empty($errors)) {
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

    update_sales_order_subtotal($item->parentId);
    update_sales_order_lastmod($item->parentId);

    $db->commit();
    header('Location: ./editor?id=' . $item->parentId);
    exit;
  }
}

$products = [];
$productByIds = [];
$q = $db->query('select id, name from products where active=1 and type <= 200 order by name asc');
while ($product = $q->fetchObject()) {
  $product->code = format_product_code($product->id);
  $product->prices = [];
  $products[] = $product;
  $productByIds[$product->id] = $product;
}

$q = $db->query('select * from product_prices order by productId asc, quantityMin asc');
while ($price = $q->fetchObject()) {
  $productByIds[$price->productId]->prices[] = $price;
  unset($price->id);
  unset($price->productId);
  foreach ($price as $key => $value) {
    $price->{$key} = $value == 0 ? null : (int)$value;
  }
}

render('pos/sales-order/item-editor', [
  'item' => $item,
  'products' => $products,
  'productByIds' => $productByIds,
  'errors' => $errors,
]);
