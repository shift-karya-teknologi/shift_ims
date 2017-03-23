<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

$groupId = $_SESSION['CURRENT_USER']->groupId;

if ($id) {
  ensure_current_user_can('edit-finance-transaction-category');
  $category = $db->query('select * from finance_transaction_categories where id=' . $id)->fetchObject();
  if (!$category) {
    $_SESSION['FLASH_MESSAGE'] = 'Kategori tidak ditemukan';
    header('Location: ./');
    exit;
  }
}
else {
  ensure_current_user_can('add-finance-transaction-category');
  $category = new stdClass();
  $category->id = null;
  $category->active = 1;
  $category->name = '';
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {
    ensure_current_user_can('delete-finance-transaction-category');
    try {
      $db->query('delete from finance_transaction_categories where id=' . $category->id);
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
  $category->active = isset($_POST['active']) ? (int)$_POST['active'] : 0;
  
  if (empty($category->name))
    $errors['name'] = 'Nama kategori harus diisi.';
    
  if (empty($errors)) {
    if ($category->id == 0) {
      $q = $db->prepare('select count(0) from finance_transaction_categories where name=:name and groupId=' . $groupId);
    }
    else {
      $q = $db->prepare('select count(0) from finance_transaction_categories where name=:name and id<>:id and groupId=' . $groupId);
      $q->bindValue(':id', $category->id);
    }
    $q->bindValue(':name', $category->name);
    $q->execute();
    
    if ($q->fetch(PDO::FETCH_COLUMN) > 0) {
      $errors['name'] = 'Nama kategori sudah digunakan.';
    }
    else {      
      if ($category->id == 0) {
        $q = $db->prepare('insert into finance_transaction_categories (name, active, groupId) values(:name,:active,:groupId)');
        $q->bindValue(':groupId', $groupId);
      }
      else {
        $q = $db->prepare('update finance_transaction_categories set name=:name, active=:active where id=:id');
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

render('finance/transaction-category/editor', [
  'category' => $category,
  'errors'   => $errors,
]);
