<?php

require_once CORELIB_PATH . '/Product.php';

$product = new Product();
$product->baseUom = new ProductUom();
$product->baseUom->quantity = 1;
$product->active = 1;
$product->type = ProductTypes::Stocked;
$product->costingMethod = ProductCostingMethods::LastPurchase;
$product->baseUom->name = 'bh';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $product->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
  $product->active = (int)filter_input(INPUT_POST, 'active', FILTER_VALIDATE_INT);
  $product->type = (int)filter_input(INPUT_POST, 'type', FILTER_VALIDATE_INT);
  $product->costingMethod = (int)filter_input(INPUT_POST, 'costingMethod', FILTER_VALIDATE_INT);
  $product->baseUom->name = filter_input(INPUT_POST, 'uom', FILTER_SANITIZE_STRING);
  
  if (empty($product->name)) {
    $errors['name'] = 'Nama produk harus diisi.';
  }
  
  if (empty($product->baseUom->name)) {
    $errors['uom'] = 'Nama satuan dasar harus diisi.';
  }
  
  if (empty($errors)) {
    $q = $db->prepare('select count(0) from products where name=?');
    $q->bindValue(1, $product->name);
    $q->execute();
    
    if ($q->fetch(PDO::FETCH_COLUMN) > 0) {
      $errors['name'] = 'Nama produk sudah digunakan.';
    }
    else {
      $db->beginTransaction();
      $q = $db->prepare('insert into products(name, type, active, costingMethod) values(?,?,?,?)');
      $q->bindValue(1, $product->name);
      $q->bindValue(2, $product->type);
      $q->bindValue(3, $product->active);
      $q->bindValue(4, $product->costingMethod);
      $q->execute();
      
      $product->id = $db->lastInsertId();
      
      $q = $db->prepare('insert into product_uoms(productId,name,quantity) values(?,?,1)');
      $q->bindValue(1, $product->id);
      $q->bindValue(2, $product->baseUom->name);
      $q->execute();
      
      $db->query("update products set baseUomId={$db->lastInsertId()} where id={$product->id}");
      
      $db->commit();
      
      $_SESSION['FLASH_MESSAGE'] = 'Produk ' . $product->name . ' telah ditambahkan ke daftar produk.';
      header('Location: ./');
      exit;
    }
  }
}

render('layout', [
  'title'   => 'Tambah Produk',
  'sidenav' => render('pos/sidenav', true),
  'content' => render('pos/product/add', [
    'product' => $product,
    'errors' => $errors,
  ], true),
]);
