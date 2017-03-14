<?php

function _ensure_user_can_access() {
  global $uom;
  if ($uom->id == 0 && !current_user_can('add-product-uom'))
    exit(render('error/403'));
  else if ($uom->id != 0 && !current_user_can('edit-product-uom'))
    exit(render('error/403'));
}

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
    if (!current_user_can('delete-product-uom'))
      exit(render('error/403'));
      
    $db->query('delete from product_uoms where id=' . $uom->id);
    $_SESSION['FLASH_MESSAGE'] = "Satuan $uom->name telah dihapus.";
    header('Location: ./editor?id=' . $uom->productId);
    exit;
  }
  
  _ensure_user_can_access();
  
  if ($action == 'save') {
    $uom->name = trim((string)$_POST['name']);
    $uom->quantity = (int)str_replace('.', '', trim((string)$_POST['quantity']));
    
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

_ensure_user_can_access();

render('pos/product/uom-editor', [
  'uom'     => $uom,
  'product' => $product,
  'errors'  => $errors,
]);