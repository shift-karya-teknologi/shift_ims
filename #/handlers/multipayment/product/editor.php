<?php

require CORELIB_PATH . '/Product.php';

ensure_current_user_can('edit-multipayment-account');

$accountId = isset($_REQUEST['accountId']) ? (int)$_REQUEST['accountId'] : 0;
$productId = isset($_REQUEST['productId']) ? (int)$_REQUEST['productId'] : 0;

$item = $oldItem = null;

if (!$accountId)
  exit(render('error/404'));

if ($productId) {
  $item = $db->query("select mp.*, a.name accountName, p.name productName"
    . " from multipayment_products mp"
    . " inner join products p on p.id=mp.productId"
    . " inner join multipayment_accounts a on a.id=mp.accountId"
    . " where mp.accountId=$accountId"
    . " and mp.productId=$productId")
    ->fetchObject();
}

if ($item)
  $oldItem = clone $item;
else {
  $item = new stdClass();
  $item->accountId = $accountId;
  $item->accountName = $db->query("select name from multipayment_accounts where id=$accountId")->fetchColumn();
  $item->productId = null;
  $item->productName = '';
  $item->cost  = 0;
  $item->price = 0;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {   
    try {
      $db->query("delete from multipayment_products where accountId=$item->accountId and productId=$item->productId" );
    }
    catch (Exception $ex) {
      $_SESSION['FLASH_MESSAGE'] = 'Rekaman tidak dapat dihapus.';
      header('Location: ../account/editor?id=' . $item->accountId);
      exit;  
    }
    
    $_SESSION['FLASH_MESSAGE'] = 'Rekaman telah dihapus.';
    header('Location: ../account/editor?id=' . $item->accountId);
    exit;
  }
  
  $item->productId = isset($_POST['productId']) ? (int)$_POST['productId'] : 0;
  $item->accountId = isset($_POST['accountId']) ? (int)$_POST['accountId'] : 0;
  $item->cost  = isset($_POST['cost']) ? (int)str_replace('.', '', $_POST['cost']) : 0;
  $item->price = isset($_POST['price']) ? (int)str_replace('.', '', $_POST['price']) : 0;
  
  if (empty($item->productId))
    $errors['productId'] = 'Produk tidak valid.';
    
  if (empty($errors)) {
    if ($oldItem === null) {
      $q = $db->prepare('insert into multipayment_products (productId, accountId, cost, price)'
        . ' values(:productId,:accountId,:cost,:price)');
    }
    else {
      $q = $db->prepare('update multipayment_products set productId=:productId, accountId=:accountId,'
        . ' cost=:cost, price=:price where productId=:oldProductId and accountId=:oldAccountId');
      $q->bindValue(':oldProductId', $oldItem->productId);
      $q->bindValue(':oldAccountId', $oldItem->accountId);
    }
    $q->bindValue(':productId', $item->productId);
    $q->bindValue(':accountId', $item->accountId);
    $q->bindValue(':cost', $item->cost);
    $q->bindValue(':price', $item->price);
    $q->execute();

    $_SESSION['FLASH_MESSAGE'] = 'Rekaman telah disimpan.';
    header('Location: ../account/editor?id=' . $item->accountId);
    exit;
  }
}

$products = $db->query('select id, name from products where type=' . Product::MultiPayment . ' order by name asc')
  ->fetchAll(PDO::FETCH_CLASS, Product::class);

render('multipayment/product/editor', [
  'item' => $item,
  'products' => $products,
  'errors'   => $errors,
]);
