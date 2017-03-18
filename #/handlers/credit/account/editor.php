<?php

require CORELIB_PATH . '/CreditAccount.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  ensure_current_user_can('edit-credit-account');
  $item = $db->query("select * from credit_accounts where id=$id")->fetchObject(CreditAccount::class);
}
else {
  ensure_current_user_can('add-credit-account');
  $item = new CreditAccount();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
  
  if ($action === 'delete') {
    ensure_current_user_can('delete-credit-account');  
    try {
      $db->query("delete from credit_accounts where id=$id");
    }
    catch (Exception $e) {
      $_SESSION['FLASH_MESSAGE'] = 'Akun ' . e($item->getCode()) . ' tidak dapat dihapus.';
      exit(header('Location: ./'));
    }
    $_SESSION['FLASH_MESSAGE'] = 'Akun ' . e($item->getCode()) . ' telah dihapus.';
    exit(header('Location: ./'));
  }
  else if ($action === 'save') {
    $item->customerName = isset($_POST['customerName']) ? trim((string)$_POST['customerName']) : '';
    $item->customerAddress = isset($_POST['customerAddress']) ? trim((string)$_POST['customerAddress']) : '';
    $item->customerContact = isset($_POST['customerContact']) ? trim((string)$_POST['customerContact']) : '';
    $item->customerId = isset($_POST['customerId']) ? trim((string)$_POST['customerId']) : '';
    $item->productName = isset($_POST['productName']) ? trim((string)$_POST['productName']) : '';
    $item->productPrice = isset($_POST['productPrice']) ? (int)str_replace('.', '', trim((string)$_POST['productPrice'])) : 0;
    $item->productSerialNumber = isset($_POST['productSerialNumber']) ? trim((string)$_POST['productSerialNumber']) : '';
    $item->administrationCost = isset($_POST['administrationCost']) ? (int)str_replace('.', '', trim((string)$_POST['administrationCost'])) : 0;
    $item->dueDate = isset($_POST['dueDate']) ? (int)$_POST['dueDate'] : 0;
    $item->referralId = isset($_POST['referralId']) ? (int)$_POST['referralId'] : 0;
    
    if (empty($item->customerName)) {
      $errors['customerName'] = 'Nama harus diisi.';
    }
    
    if (empty($item->productName)) {
      $errors['productName'] = 'Produk harus diisi.';
    }
    
    if (empty($item->productPrice)) {
      $errors['productPrice'] = 'Harga produk harus diisi.';
    }
    
    if ($item->dueDate < 1 || $item->dueDate >= 27) {
      $errors['dueDate'] = 'Tanggal jatuh tempo tidak valid.';
    }
    
    if (empty($errors)) {
      $now = date('Y-m-d H:i:s');
      $db->beginTransaction();
      if (!$item->id) {
        $q = $db->prepare('insert into credit_accounts'
          . ' ( customerName, customerAddress, customerContact, customerId,'
          . '   productName, productPrice, productSerialNumber,'
          . '   administrationCost, dueDate, referralId,'
          . '   creationDateTime, creationUserId, lastModDateTime, lastModUserId,'
          . '   balance)'
          . ' values'
          . ' (:customerName,:customerAddress,:customerContact,:customerId,'
          . '  :productName,:productPrice,:productSerialNumber,'
          . '  :administrationCost,:dueDate,:referralId,'
          . '  :creationDateTime,:creationUserId,:lastModDateTime,:lastModUserId,'
          . '  :balance)');
        $q->bindValue(':creationUserId', $_SESSION['CURRENT_USER']->id);
        $q->bindValue(':creationDateTime', $now);
        $q->bindValue(':balance', $item->productPrice + $item->administrationCost);
      }
      else {
        $q = $db->prepare('update credit_accounts set'
          . ' customerName=:customerName, customerAddress=:customerAddress, customerContact=:customerContact, customerId=:customerId,'
          . ' productName=:productName, productPrice=:productPrice, productSerialNumber=:productSerialNumber,'
          . ' administrationCost=:administrationCost, dueDate=:dueDate, referralId=:referralId,'
          . ' lastModDateTime=:lastModDateTime, lastModUserId=:lastModUserId'
          . ' where id=:id');
        $q->bindValue(':id', $item->id);
      }
      $q->bindValue(':customerName', $item->customerName);
      $q->bindValue(':customerAddress', $item->customerAddress);
      $q->bindValue(':customerContact', $item->customerContact);
      $q->bindValue(':customerId', $item->customerId);
      $q->bindValue(':productName', $item->productName);
      $q->bindValue(':productPrice', $item->productPrice);
      $q->bindValue(':productSerialNumber', $item->productSerialNumber);
      $q->bindValue(':administrationCost', $item->administrationCost);
      $q->bindValue(':dueDate', $item->dueDate);
      $q->bindValue(':referralId', $item->referralId);
      $q->bindValue(':lastModDateTime', $now);
      $q->bindValue(':lastModUserId', $_SESSION['CURRENT_USER']->id);
      $q->execute();
      CreditAccount::updateBalance($item->id);
      $db->commit();
      
      if (!$item->id) $item->id = $db->lastInsertId();
      
      $_SESSION['FLASH_MESSAGE'] = 'Akun ' . $item->getCode() . ' telah disimpan.';
      exit(header('Location: ./view?id=' . $item->id));
    }
  }
}

$referrals = $db->query('select * from credit_referrals order by name asc')->fetchAll(PDO::FETCH_OBJ);
render('credit/account/editor', [
  'item' => $item,
  'errors' => $errors,
  'referrals' => $referrals,
]);