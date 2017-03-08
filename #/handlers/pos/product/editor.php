<?php

require_once CORELIB_PATH . '/Product.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  if ($_SESSION['CURRENT_USER']->groupId != 1) {
    http_response_code(403);
    render('error/403');
    exit;
  }
  
  $product = $db->query('select * from products where id=' . $id)->fetchObject(Product::class);
  if (!$product) {
    $_SESSION['FLASH_MESSAGE'] = 'Produk ' . format_product_code($id) . ' tidak ditemukan';
    header('Location: ./');
    exit;
  }
  $product->prices = $db->query('select * from product_prices where productId=' . $product->id)->fetchAll(PDO::FETCH_OBJ);
  $product->uoms = $db->query('select * from product_uoms where productId=' . $product->id)->fetchAll(PDO::FETCH_OBJ);
}
else {
  $product = new Product();
  $product->active = 1;
  $product->type = Product::Stocked;
  $product->costingMethod = Product::ManualCostingMethod;
  $product->cost = 0;
  $product->uom = 'bh';
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {
    try {
      $db->query('delete from products where id='.$product->id);
    }
    catch (Exception $ex) {
      $_SESSION['FLASH_MESSAGE'] = 'Produk tidak dapat dihapus.';
      header('Location: ?id='. $product->id);
      exit;  
    }
    
    $_SESSION['FLASH_MESSAGE'] = 'Produk ' . format_product_code($product->id) . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
  
  $product->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
  $product->active = (int)filter_input(INPUT_POST, 'active', FILTER_VALIDATE_INT);
  $product->type = (int)filter_input(INPUT_POST, 'type', FILTER_VALIDATE_INT);
  $product->costingMethod = (int)filter_input(INPUT_POST, 'costingMethod', FILTER_VALIDATE_INT);
  $product->manualCost = (string)filter_input(INPUT_POST, 'manualCost', FILTER_SANITIZE_STRING);
  $product->uom = filter_input(INPUT_POST, 'uom', FILTER_SANITIZE_STRING);
  
  if (empty($product->name)) {
    $errors['name'] = 'Nama produk harus diisi.';
  }
  
  if (empty($product->uom)) {
    $errors['uom'] = 'Nama satuan dasar harus diisi.';
  }
  
  
  if (!preg_match('/^((?:\d{1,3}[\.]?)+\d*)$/', $product->manualCost)) {
    $errors['manualCost'] = 'Nilai modal tidak valid.';
  }
  else {
    $product->manualCost = (int)str_replace('.', '', $product->manualCost);
  }
  
  if (empty($errors)) {
    
    if ($product->id == 0) {
      $q = $db->prepare('select count(0) from products where name=:name');
    }
    else {
      $q = $db->prepare('select count(0) from products where name=:name and id<>:id');
      $q->bindValue(':id', $product->id);
    }
    $q->bindValue(':name', $product->name);
    $q->execute();
    
    if ($q->fetch(PDO::FETCH_COLUMN) > 0) {
      $errors['name'] = 'Nama produk sudah digunakan.';
    }
    else {
      switch ($product->costingMethod) {
        case Product::ManualCostingMethod: $product->cost = $product->manualCost; break;
        case Product::AverageCostingMethod: $product->cost = $product->averageCost; break;
        case Product::LastPurchaseCostingMethod: $product->cost = $product->lastPurchaseCost; break;
      }
      
      if ($product->cost == 0 && $product->manualCost != 0)
        $product->cost = $product->manualCost;
      if ($product->averageCost == 0 && $product->manualCost != 0)
        $product->averageCost = $product->manualCost;
      if ($product->lastPurchaseCost == 0 && $product->manualCost != 0)
        $product->lastPurchaseCost = $product->manualCost;
      
      if ($product->id == 0) {
        $q = $db->prepare('insert into products'
          . ' ( name, type, active, costingMethod, uom, cost, manualCost, averageCost, lastPurchaseCost)'
          . ' values'
          . ' (:name,:type,:active,:costingMethod,:uom,:cost,:manualCost,:averageCost,:lastPurchaseCost)');
        $q->bindValue(':lastPurchaseCost', $product->cost);
        $q->bindValue(':averageCost', $product->cost);
      }
      else {
        $q = $db->prepare('update products set'
          . ' name=:name,'
          . ' type=:type,'
          . ' active=:active,'
          . ' costingMethod=:costingMethod,'
          . ' uom=:uom,'
          . ' cost=:cost,'
          . ' manualCost=:manualCost'
          . ' where id=:id');
        $q->bindValue(':id', $product->id);
      }
      
      $q->bindValue(':name', $product->name);
      $q->bindValue(':type', $product->type);
      $q->bindValue(':active', $product->active);
      $q->bindValue(':costingMethod', $product->costingMethod);
      $q->bindValue(':cost', $product->cost);
      $q->bindValue(':uom', $product->uom);
      $q->bindValue(':manualCost', $product->manualCost);
      $q->execute();
      
      if (!$product->id)
        $product->id = $db->lastInsertId();
      
      $_SESSION['FLASH_MESSAGE'] = 'Produk telah disimpan.';
      header('Location: ./editor?id=' . $product->id);
      exit;
    }
  }
}

render('layout', [
  'title'   => $product->id ? format_product_code($product->id) : 'Tambah Produk',
  'sidenav' => render('pos/sidenav', true),
  'headnav' => '
    <a href="./" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">close</i>
    </a>',
  'content' => render('pos/product/editor', [
    'product' => $product,
    'errors' => $errors,
  ], true),
]);
