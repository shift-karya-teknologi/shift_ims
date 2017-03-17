<?php

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-credit-referral');
  $item = $db->query("select * from credit_referrals where id=$id")->fetchObject();
}
else {
  ensure_current_user_can('add-credit-referral');
  $item = new stdClass();
  $item->id = 0;
  $item->name = '';
  $item->active = 1;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
  
  if ($action === 'delete') {
    ensure_current_user_can('delete-credit-referral');  
    try {
    $db->query("delete from credit_referrals where id=$id");
    }
    catch (Exception $e) {
      $_SESSION['FLASH_MESSAGE'] = 'Referal ' . e($item->name) . ' tidak dapat dihapus.';
      exit(header('Location: ./'));
    }
    $_SESSION['FLASH_MESSAGE'] = 'Referal ' . e($item->name) . ' telah dihapus.';
    exit(header('Location: ./'));
  }
  else if ($action === 'save') {
    $item->name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $item->active = isset($_POST['active']) ? (int)$_POST['active'] : 0;
    
    if (empty($item->name)) {
      $errors['name'] = 'Nama harus diisi.';
    }
    else if (mb_strlen($item->name) > 100) {
      $errors['name'] = 'Nama referal maksimal 100 karakter.';
    }
    else {
      $sql = 'select count(0) from credit_referrals where name=?';
      if ($id) $sql .= "and id<>$id";
      $q = $db->prepare($sql);
      $q->bindValue(1, $item->name);
      $q->execute();
      if ($q->fetchColumn())
        $errors['name'] = 'Nama referal sudah digunakan.';
    }
    
    if (empty($errors)) {
      if (!$id) {
        $q = $db->prepare('insert into credit_referrals'
          . ' ( name, active)'
          . ' values'
          . ' (:name,:active)');
      }
      else {
        $q = $db->prepare('update credit_referrals'
          . ' set name=:name, active=:active'
          . ' where id=:id');
        $q->bindValue(':id', $item->id);
      }
      $q->bindValue(':name', $item->name);
      $q->bindValue(':active', $item->active);
      $q->execute();
      
      if (!$item->id) $item->id = $db->lastInsertId();
      
      $_SESSION['FLASH_MESSAGE'] = 'Referal ' . $item->id . ' telah disimpan.';
      exit(header('Location: ./'));
    }
  }
}

render('credit/referral/editor', [
  'item' => $item,
  'errors' => $errors,
]);