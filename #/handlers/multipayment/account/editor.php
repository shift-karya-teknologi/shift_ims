<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-multipayment-account');
  
  $account = $db->query('select * from multipayment_accounts where id=' . $id)->fetchObject();
  if (!$account) {
    $_SESSION['FLASH_MESSAGE'] = 'Akun tidak ditemukan';
    header('Location: ./');
    exit;
  }
}
else {
  ensure_current_user_can('add-multipayment-account');
  
  $account = new stdClass();
  $account->id = null;
  $account->active = 1;
  $account->name = '';
  $account->balance = 0;
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {
    ensure_current_user_can('delete-multipayment-account');
    
    try {
      $db->query('delete from multipayment_accounts where id=' . $account->id);
    }
    catch (Exception $ex) {
      $_SESSION['FLASH_MESSAGE'] = 'Akun tidak dapat dihapus.';
      header('Location: ?id=' . $account->id);
      exit;  
    }
    
    $_SESSION['FLASH_MESSAGE'] = 'Akun ' . e($account->name) . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
  
  $account->name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
  $account->active = isset($_POST['active']) ? (int)$_POST['active'] : 0;
  
  if (empty($account->name))
    $errors['name'] = 'Nama akun harus diisi.';
    
  if (empty($errors)) {
    if ($account->id == 0) {
      $q = $db->prepare('select count(0) from multipayment_accounts where name=:name');
    }
    else {
      $q = $db->prepare('select count(0) from multipayment_accounts where name=:name and id<>:id');
      $q->bindValue(':id', $account->id);
    }
    $q->bindValue(':name', $account->name);
    $q->execute();
    
    if ($q->fetch(PDO::FETCH_COLUMN) > 0) {
      $errors['name'] = 'Nama akun sudah digunakan.';
    }
    else {      
      if ($account->id == 0) {
        $q = $db->prepare('insert into multipayment_accounts (name, active, balance) values(:name,:active,0)');
      }
      else {
        $q = $db->prepare('update multipayment_accounts set name=:name, active=:active where id=:id');
        $q->bindValue(':id', $account->id);
      }
      $q->bindValue(':name', $account->name);
      $q->bindValue(':active', $account->active);
      $q->execute();
      
      $_SESSION['FLASH_MESSAGE'] = 'Akun ' . e($account->name). ' telah disimpan.';
      header('Location: ./');
      exit;
    }
  }
}

if ($account->id) {
  $products = $db->query('select mp.*, p.name productName'
    . ' from multipayment_products mp'
    . ' inner join products p on p.id=mp.productId'
    . ' where mp.accountId=' . $account->id
    . ' order by p.name asc')
    ->fetchAll(PDO::FETCH_OBJ);
}

render('multipayment/account/editor', [
  'account' => $account,
  'products' => $products,
  'errors'   => $errors,
]);
