<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-debts-account');
  $account = $db->query('select * from debts_accounts where id=' . $id)->fetchObject();
  if (!$account) {
    $_SESSION['FLASH_MESSAGE'] = 'Akun tidak ditemukan';
    header('Location: ./');
    exit;
  }
}
else {
  ensure_current_user_can('add-debts-account');
  $account = new stdClass();
  $account->id = 0;
  $account->name = '';
  $account->type = 0;
  $account->active = 1;
  
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {
    ensure_current_user_can('delete-debts-account');
    try {
      $db->query('delete from debts_accounts where id=' . $account->id);
    }
    catch (Exception $ex) {
      $_SESSION['FLASH_MESSAGE'] = 'Akun tidak dapat dihapus.';
      header('Location: ?id=' . $account->id);
      exit;  
    }
    
    $_SESSION['FLASH_MESSAGE'] = 'Akun ' . $account->name . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
  
  $account->name = isset($_POST['name']) ? (string)$_POST['name'] : '';
  $account->type = isset($_POST['type']) ? (int)$_POST['type'] : 0;
  
  if (empty($account->name))
    $errors['name'] = 'Nama akun harus diisi.';
    
  if (empty($errors)) {
    if ($account->id == 0) {
      $q = $db->prepare('select count(0) from debts_accounts where name=:name');
    }
    else {
      $q = $db->prepare('select count(0) from debts_accounts where name=:name and id<>:id');
      $q->bindValue(':id', $account->id);
    }
    $q->bindValue(':name', $account->name);
    $q->execute();
    
    if ($q->fetch(PDO::FETCH_COLUMN) > 0) {
      $errors['name'] = 'Nama akun sudah digunakan.';
    }
    else {
      $db->beginTransaction();
      if ($account->id == 0) {
        $q = $db->prepare('insert into debts_accounts (name, type, balance, active) values(:name,:type, 0,:active)');
      }
      else {
        $q = $db->prepare('update debts_accounts set name=:name, type=:type, active=:active where id=:id');
        $q->bindValue(':id', $account->id);
      }
      $q->bindValue(':type', $account->type);
      $q->bindValue(':name', $account->name);
      $q->bindValue(':active', $account->active);
      $q->execute();
      
      $db->commit();
      
      $_SESSION['FLASH_MESSAGE'] = 'Akun ' . $account->name . ' telah disimpan.';
      header('Location: ./');
      exit;
    }
  }
}

render('finance/debts/editor', [
  'account' => $account,
  'errors'   => $errors,
]);
