<?php

require_once CORELIB_PATH . '/Product.php';

$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$productId = (int)(isset($_REQUEST['productId']) ? $_REQUEST['productId'] : 0);
$uom = null;
$errors = [];

if (!$id) {
  $uom = new ProductUom();
  $uom->productId = $productId;
}
else {
  $uom = $db->query('select * from product_uoms where id=' . $id)->fetchObject(ProductUom::class);
}

$product = $db->query('select * from products where id=' . $uom->productId)->fetchObject();
if (!$product) {
  header('Location: ./');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];
  
  if ($action == 'delete') {
    $db->query('delete from product_uoms where id=' . $uom->id);
    $_SESSION['FLASH_MESSAGE'] = "Satuan $uom->name telah dihapus.";
    header('Location: ./editor?id=' . $uom->productId);
    exit;
  }
  
  if ($action == 'save') {
    $uom->name = (string)$_POST['name'];
    $uom->quantity = (int)str_replace('.', '', (string)$_POST['quantity']);
    
    if (!$uom->id) {
      $q = $db->prepare('insert into product_uoms( productId, name, quantity) values (:productId,:name,:quantity)');
      $q->bindValue(':productId', $uom->productId);
    }
    else {
      $q = $db->prepare('update product_uoms set name=:name, quantity=:quantity where id=:id');
      $q->bindValue(':id', $uom->id);
    }
    $q->bindValue(':name', $uom->name);
    $q->bindValue(':quantity', $uom->quantity);
    $q->execute();
    
    $_SESSION['FLASH_MESSAGE'] = "Satuan telah disimpan.";
    header('Location: ./editor?id=' . $uom->productId);
    exit;
  }
}

render('pos/product/uom-editor', [
  'uom'     => $uom,
  'product' => $product,
  'errors'  => $errors,
]);