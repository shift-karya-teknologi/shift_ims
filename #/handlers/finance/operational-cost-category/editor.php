<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-operational-cost-category');
  $category = $db->query('select * from operational_cost_categories where id=' . $id)->fetchObject();
  if (!$category) {
    $_SESSION['FLASH_MESSAGE'] = 'Kategori tidak ditemukan';
    header('Location: ./');
    exit;
  }
}
else {
  ensure_current_user_can('add-operational-cost-category');
  $category = new stdClass();
  $category->id = null;
  $category->active = 1;
  $category->name = '';
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {
    try {
      $db->query('delete from operational_cost_categories where id=' . $category->id);
    }
    catch (Exception $ex) {
      $_SESSION['FLASH_MESSAGE'] = 'Kategori tidak dapat dihapus.';
      header('Location: ?id=' . $category->id);
      exit;  
    }
    
    $_SESSION['FLASH_MESSAGE'] = 'Kategori ' . $category->name . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
  
  $category->name = isset($_POST['name']) ? (string)$_POST['name'] : '';
  $category->active = isset($_POST['active']) ? (int)$_POST['active'] : false;
  
  if (empty($category->name))
    $errors['name'] = 'Nama kategori harus diisi.';
    
  if (empty($errors)) {
    if ($category->id == 0) {
      $q = $db->prepare('select count(0) from operational_cost_categories where name=:name');
    }
    else {
      $q = $db->prepare('select count(0) from operational_cost_categories where name=:name and id<>:id');
      $q->bindValue(':id', $category->id);
    }
    $q->bindValue(':name', $category->name);
    $q->execute();
    
    if ($q->fetch(PDO::FETCH_COLUMN) > 0) {
      $errors['name'] = 'Nama kategori sudah digunakan.';
    }
    else {      
      if ($category->id == 0) {
        $q = $db->prepare('insert into operational_cost_categories (name, active) values(:name,:active)');
      }
      else {
        $q = $db->prepare('update operational_cost_categories set name=:name, active=:active where id=:id');
        $q->bindValue(':id', $category->id);
      }
      $q->bindValue(':name', $category->name);
      $q->bindValue(':active', $category->active);
      $q->execute();
      
      $_SESSION['FLASH_MESSAGE'] = 'Kategori ' . $category->name . ' telah disimpan.';
      header('Location: ./');
      exit;
    }
  }
}

render('finance/operational-cost-category/editor', [
  'category' => $category,
  'errors'   => $errors,
]);
