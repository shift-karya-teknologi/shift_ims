<?php

require CORELIB_PATH . '/FinanceAccount.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-finance-account');
  $account = $db->query('select * from finance_accounts where id=' . $id)->fetchObject(FinanceAccount::class);
  if (!$account) {
    $_SESSION['FLASH_MESSAGE'] = 'Akun tidak ditemukan';
    header('Location: ./');
    exit;
  }
  
  $q = $db->query('select userId from finance_account_users where accountId=' . $account->id);
  while ($item = $q->fetchObject()) {
    $account->users[] = $item->userId;
  }
}
else {
  ensure_current_user_can('add-finance-account');
  $account = new FinanceAccount();
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {
    ensure_current_user_can('delete-finance-account');
    try {
      $db->query('delete from finance_accounts where id=' . $account->id);
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
  $account->active = isset($_POST['active']) ? (int)$_POST['active'] : false;
  $account->users = isset($_POST['users']) ? (array)$_POST['users'] : [];
  
  if (empty($account->name))
    $errors['name'] = 'Nama akun harus diisi.';
    
  if (empty($errors)) {
    if ($account->id == 0) {
      $q = $db->prepare('select count(0) from finance_accounts where name=:name');
    }
    else {
      $q = $db->prepare('select count(0) from finance_accounts where name=:name and id<>:id');
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
        $q = $db->prepare('insert into finance_accounts (name, active, balance) values(:name,:active,0)');
      }
      else {
        $q = $db->prepare('update finance_accounts set name=:name, active=:active where id=:id');
        $q->bindValue(':id', $account->id);
      }
      $q->bindValue(':name', $account->name);
      $q->bindValue(':active', $account->active);
      $q->execute();
      
      if (!$account->id)
        $account->id = $db->lastInsertId();
      
      $db->query("delete from finance_account_users where accountId=$account->id");
      
      foreach ($account->users as $userId) {
        $userId = (int)$userId;
        $db->query("insert into finance_account_users (userId, accountId) values($userId, $account->id)");
      }
      
      FinanceAccount::updateBalance($account->id);
      
      $db->commit();
      
      $_SESSION['FLASH_MESSAGE'] = 'Akun ' . $account->name . ' telah disimpan.';
      header('Location: ./');
      exit;
    }
  }
}

$users = $db->query('select id, username from users order by username asc')->fetchAll(PDO::FETCH_OBJ);

render('finance/account/editor', [
  'account' => $account,
  'users'   => $users,
  'errors'   => $errors,
]);
