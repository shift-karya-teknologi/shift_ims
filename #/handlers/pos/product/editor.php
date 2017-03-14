<?php

function _ensure_user_can_access() {
  global $product;
  if ($product->id == 0 && !current_user_can('add-product'))
    exit(render('error/403'));
  else if ($product->id != 0 && !current_user_can('edit-product'))
    exit(render('error/403'));
}

require_once CORELIB_PATH . '/Product.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) { 
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
  $product->costingMethod = Product::LastPurchaseCostingMethod;
  $product->cost = 0;
  $product->uom = 'bh';
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {
    if (!current_user_can('delete-product'))
      exit(render('error/403'));
    
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
  
  _ensure_user_can_access();
  
  $product->name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
  $product->active = (int)filter_input(INPUT_POST, 'active', FILTER_VALIDATE_INT);
  $product->costingMethod = (int)filter_input(INPUT_POST, 'costingMethod', FILTER_VALIDATE_INT);
  $product->uom = isset($_POST['uom']) ? trim((string)$_POST['uom']) : '';
  $product->categoryId = (int)filter_input(INPUT_POST, 'categoryId', FILTER_VALIDATE_INT);
  
  if (!$product->id) {
    $product->type = (int)filter_input(INPUT_POST, 'type', FILTER_VALIDATE_INT);
  }
  
  if (isset($_POST['manualCost'])) {
    $product->manualCost = trim((string)$_POST['manualCost']);
  }
  
  if (empty($product->name)) {
    $errors['name'] = 'Nama produk harus diisi.';
  }
  else {
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
  }
  
  if (empty($product->uom)) {
    $errors['uom'] = 'Nama satuan dasar harus diisi.';
  }
  
  if ($product->type == Product::Stocked) {
    if (!preg_match('/^((?:\d{1,3}[\.]?)+\d*)$/', $product->manualCost) || $product->manualCost == 0) {
      $errors['manualCost'] = 'Nilai modal tidak valid.';
    }
    else {
      $product->manualCost = (int)str_replace('.', '', $product->manualCost);
      
      switch ($product->costingMethod) {
        case Product::ManualCostingMethod: $product->cost = $product->manualCost; break;
        case Product::AverageCostingMethod: $product->cost = $product->averageCost; break;
        case Product::LastPurchaseCostingMethod: $product->cost = $product->lastPurchaseCost; break;
      }

      if (!$product->cost && $product->manualCost != 0)
        $product->cost = $product->manualCost;
      if (!$product->averageCost && $product->manualCost != 0)
        $product->averageCost = $product->manualCost;
      if (!$product->lastPurchaseCost && $product->manualCost != 0)
        $product->lastPurchaseCost = $product->manualCost;
    }
  }
  else {
    $product->cost = 0;
    $product->manualCost = 0;
    $product->lastPurchaseCost = 0;
    $product->averageCost = 0;
  }
  
  if (empty($errors)) {
    if ($product->id == 0) {
      $q = $db->prepare('insert into products'
        . ' ( name, type, active, costingMethod, uom, cost, manualCost, averageCost, lastPurchaseCost, categoryId)'
        . ' values'
        . ' (:name,:type,:active,:costingMethod,:uom,:cost,:manualCost,:averageCost,:lastPurchaseCost,:categoryId)');
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
        . ' manualCost=:manualCost,'
        . ' categoryId=:categoryId'
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
    $q->bindValue(':categoryId', $product->categoryId ? $product->categoryId : null);
    $q->execute();

    if (!$product->id) {
      $product->id = $db->lastInsertId();
      $_SESSION['FLASH_MESSAGE'] = 'Produk telah disimpan. Silahkan perbarui harga dan satuan alternatif.';
      header('Location: ./editor?id=' . $product->id);
    }
    else {
      update_product_quantity($product->id);
      $_SESSION['FLASH_MESSAGE'] = 'Produk ' . format_product_code($product->id). ' telah disimpan.';
      header('Location: ./');
    }
    exit;
  }
}

_ensure_user_can_access();

$categories = $db->query('select * from product_categories order by name asc')->fetchAll(PDO::FETCH_OBJ);
$multiPaymentAccounts = $db->query('select * from multipayment_accounts order by name asc')->fetchAll(PDO::FETCH_OBJ);

render('pos/product/editor', [
  'product' => $product,
  'categories' => $categories,
  'multipaymentAccounts' => $multiPaymentAccounts,
  'errors' => $errors,
]);
