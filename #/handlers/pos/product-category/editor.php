<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-product-category');
  $category = $db->query('select * from product_categories where id=' . $id)->fetchObject();
  if (!$category) {
    $_SESSION['FLASH_MESSAGE'] = 'Kategori Produk tidak ditemukan';
    header('Location: ./');
    exit;
  }
}
else {
  ensure_current_user_can('add-product-category');
  $category = new stdClass();
  $category->id = null;
  $category->name = '';
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {
    ensure_current_user_can('delete-product-category');
    
    try {
      $db->query('delete from product_categories where id=' . $category->id);
    }
    catch (Exception $ex) {
      $_SESSION['FLASH_MESSAGE'] = 'Kategori tidak dapat dihapus.';
      header('Location: ?id=' . $category->id);
      exit;  
    }
    
    $_SESSION['FLASH_MESSAGE'] = 'Kategori ' . e($category->name) . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
  
  $category->name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
  
  if (empty($category->name)) {
    $errors['name'] = 'Nama kategori harus diisi.';
  }
    
  if (empty($errors)) {
    if ($category->id == 0) {      
      $q = $db->prepare('select count(0) from product_categories where name=:name');
    }
    else {
      $q = $db->prepare('select count(0) from product_categories where name=:name and id<>:id');
      $q->bindValue(':id', $category->id);
    }
    $q->bindValue(':name', $category->name);
    $q->execute();
    
    if ($q->fetch(PDO::FETCH_COLUMN) > 0) {
      $errors['name'] = 'Nama kategori sudah digunakan.';
    }
    else {
      if ($category->id == 0) {      
        $q = $db->prepare('insert into product_categories (name) values(:name)');
      }
      else {      
        $q = $db->prepare('update product_categories set name=:name where id=:id');
        $q->bindValue(':id', $category->id);
      }
      $q->bindValue(':name', $category->name);
      $q->execute();
      
      $_SESSION['FLASH_MESSAGE'] = 'Kategori ' . e($category->name). ' telah disimpan.';
      header('Location: ./');
      exit;
    }
  }
}

render('pos/product-category/editor', [
  'category' => $category,
  'errors'   => $errors,
]);
