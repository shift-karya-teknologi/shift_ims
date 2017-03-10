<?php

class PurchaseOrderItem {
  public $id;
  public $parentId;
  public $productId;
  public $productName;
  public $quantity = 0;
  public $cost = 0;
  public $subtotalCost = 0;
}

$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$orderId = (int)(isset($_REQUEST['orderId']) ? $_REQUEST['orderId'] : 0);

if (!$id) {
  $item = new PurchaseOrderItem();
  $item->parentId = $orderId;
}
else {
  $item = $db->query('select * from purchasing_order_details where id='.$id)->fetchObject(PurchaseOrderItem::class);
  $orderId = $item->parentId;
}

$order = $db->query('select * from purchasing_orders where id=' . $orderId)->fetchObject();
if (!$order || $order->status != 0) {
  header('Location: ./editor?id=' . $orderId);
  exit;
}

$products = $db->query('select id, name, cost from products where active=1 and type=0 order by name asc')->fetchAll(PDO::FETCH_OBJ);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'];
  if ($action == 'delete') {
    $db->beginTransaction();
    $db->query('delete from purchasing_order_details where id=' . $id);
    update_sales_order_subtotal($orderId);
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Item telah dihapus';
    header('Location: ./editor?id=' . $orderId);
    exit;
  }
  
  $item->productId = (int)$_POST['productId'];
  $item->quantity = (int)str_replace('.', '', (string)$_POST['quantity']);
  $item->cost = (int)str_replace('.', '', (string)$_POST['cost']);
  
  if ($item->productId === 0) {
    $errors['productId'] = 'Anda belum memilih produk.';
  }
  else {
    // cek duplikat
    $sql = "select count(0) from purchasing_order_details"
      . " where parentId=$item->parentId and productId=$item->productId";
          
    if ($item->id)
      $sql .= " and id<>$item->id";
    if ((int)$db->query($sql)->fetchColumn() !== 0)
      $errors['productId'] = 'Produk sudah ada.';
  }
  
  if ($item->quantity === 0) {
    $errors['quantity'] = 'Kwantitas harus diisi.';
  }
  
  if ($item->cost === 0) {
    $errors['quantity'] = 'Harga beli harus diisi.';
  }
  
  if (empty($errors)) {
    $db->beginTransaction();
    if (!$item->id) {
      $q = $db->prepare('insert into purchasing_order_details'
        . ' ( parentId, productId, quantity, cost, subtotalCost)'
        . ' values '
        . ' (:parentId,:productId,:quantity,:cost,:subtotalCost)');
      $q->bindValue(':parentId', $item->parentId);
    }
    else {
      $q = $db->prepare('update purchasing_order_details set'
        . ' productId=:productId,'
        . ' quantity=:quantity,'
        . ' cost=:cost,'
        . ' subtotalCost=:subtotalCost'
        . ' where id=:id');
      $q->bindValue(':id', $item->id);
    }
    $q->bindValue(':productId', $item->productId);
    $q->bindValue(':quantity', $item->quantity);
    $q->bindValue(':cost', $item->cost);
    $q->bindValue(':subtotalCost', $item->cost * $item->quantity);
    $q->execute();

    update_purchasing_order_subtotal($orderId);

    $db->commit();
      
    $_SESSION['FLASH_MESSAGE'] = 'Item telah disimpan';
    header('Location: ./editor?id='.$orderId);
    exit;
  }
}

if ($item->productId)
  $item->productName = $db->query("select name from products where id=$item->productId")->fetchColumn();

render('pos/purchasing-order/item-editor', [
  'item' => $item,
  'products' => $products,
  'errors' => $errors
]);
