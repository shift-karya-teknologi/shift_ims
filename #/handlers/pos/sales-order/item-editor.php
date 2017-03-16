<?php

require CORELIB_PATH . '/Product.php';

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
  public $multiPaymentAccountId = null;
  public $productType;
}

$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
if (!$id) {
  ensure_current_user_can('add-sales-order-item');
  $item = new SalesOrderItem();
  $item->parentId = (int)(isset($_REQUEST['orderId']) ? $_REQUEST['orderId'] : 0);
}
else {
  ensure_current_user_can('edit-sales-order-item');
  $item = $db->query('select'
    . ' d.*, p.name productName, p.type productType'
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

$products = [];
$productByIds = [];
$q = $db->query('select id, name, type from products where active=1 and type <= 200 order by name asc');
while ($product = $q->fetchObject()) {
  $product->code = format_product_code($product->id);
  $product->prices = [];
  $products[] = $product;
  $productByIds[$product->id] = $product;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'];
  if ($action == 'delete') {
    ensure_current_user_can('delete-sales-order-item');
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
  $item->multiPaymentAccountId = null;
  
  if (!$item->productId) {
    $item->productName = '';
    $errors['productId'] = 'Silahkan pilih produk.';
  }
  else if ($productByIds[$item->productId]->type == Product::MultiPayment) {
    $item->cost = str_replace('.', '', (string)$_POST['cost']);
    $item->multiPaymentAccountId = $_POST['multiPaymentAccountId'];
  }
  
  if ($item->quantity <= 0) {
    $errors['quantity'] = 'Kwantitas harus diisi.';
  }
  
  if (empty($errors)) {
    $db->beginTransaction();
    if (!$item->id) {
      $q = $db->prepare('insert into sales_order_details'
        . ' ( parentId, productId, quantity, cost, price, subtotalCost, subtotalPrice, multiPaymentAccountId)'
        . ' values '
        . ' (:parentId,:productId,:quantity,:cost,:price,:subtotalCost,:subtotalPrice,:multiPaymentAccountId)');
      $q->bindValue(':parentId', $item->parentId);
    }
    else {
      $q = $db->prepare('update sales_order_details set'
        . ' productId=:productId,'
        . ' quantity=:quantity,'
        . ' cost=:cost,'
        . ' price=:price,'
        . ' subtotalCost=:subtotalCost,'
        . ' subtotalPrice=:subtotalPrice,'
        . ' multiPaymentAccountId=:multiPaymentAccountId'
        . ' where id=:id');
      $q->bindValue(':id', $item->id);
    }
    $q->bindValue(':productId', $item->productId);
    $q->bindValue(':quantity', $item->quantity);
    $q->bindValue(':cost', $item->cost);
    $q->bindValue(':price', $item->price);
    $q->bindValue(':subtotalCost', $item->cost * $item->quantity);
    $q->bindValue(':subtotalPrice', $item->price * $item->quantity);
    $q->bindValue(':multiPaymentAccountId', $item->multiPaymentAccountId ? $item->multiPaymentAccountId : null);
    $q->execute();

    update_sales_order_subtotal($item->parentId);
    update_sales_order_lastmod($item->parentId);

    $db->commit();
    header('Location: ./editor?id=' . $item->parentId);
    exit;
  }
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

$multiPaymentAccounts = $db->query('select id, name from multipayment_accounts where active=1 order by name asc')->fetchAll(PDO::FETCH_OBJ);

render('pos/sales-order/item-editor', [
  'item' => $item,
  'products' => $products,
  'productByIds' => $productByIds,
  'multiPaymentAccounts' => $multiPaymentAccounts,
  'errors' => $errors,
]);
