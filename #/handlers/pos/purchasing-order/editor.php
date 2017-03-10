<?php

require_once CORELIB_PATH . '/Product.php';

$now = date('Y-m-d H:i:s');
$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$order = $db->query('select * from purchasing_orders where id=' . $id)->fetchObject();
if (!$order) {
  if ($id) {
    $_SESSION['FLASH_MESSAGE'] = 'Pembelian #' . format_purchasing_order_code($id) . ' tidak ditemukan.';
  }
  header('Location: ./');
  exit;
}

$order->items = $db->query('select d.*,'
  . ' p.name productName, p.uom productUom'
  . ' from purchasing_order_details d'
  . ' inner join products p on p.id = d.productId'
  . ' where parentId=' . $order->id)->fetchAll(PDO::FETCH_OBJ);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];
  
  if ($order->status == 0 && $action == 'complete') {
    $productByIds = [];
    
    $db->beginTransaction();
    
    $updateId = add_stock_update(3, $now);
    $productIds = [];
    foreach ($order->items as $item) {
      add_stock_update_detail($updateId, $item->productId, $item->quantity);
      update_product_quantity($item->productId);
      $productIds[] = $item->productId;
    }
    
    $q = $db->prepare('update purchasing_orders set'
      . ' status=1, closeDateTime=:dateTime, lastModDateTime=:dateTime, updateId=:updateId'
      . ' where id=' . $id);
    $q->bindValue(':dateTime', $now);
    $q->bindValue(':updateId', $updateId);
    $q->execute();
    
    require_once CORELIB_PATH . '/Product.php';
    
    update_product_cost($productIds);
    
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Pembelian #' . format_purchasing_order_code($id) . ' telah selesai.';
    header('Location: ./editor?id=' . $id);
    exit;
  }
  else if ($order->status == 0 && $action == 'cancel') {
    $db->beginTransaction();
    $q = $db->prepare('update purchasing_orders set'
      . ' status=2, closeDateTime=:dateTime, lastModDateTime=:dateTime'
      . ' where id=' . $id);
    $q->bindValue(':dateTime', $now);
    $q->execute();
    
    $db->commit();
    $_SESSION['FLASH_MESSAGE'] = 'Pembelian #' . format_purchasing_order_code($id) . ' telah dibatalkan.';
    header('Location: ./');
    exit;
  }
  else if ($action == 'delete' && $order->status > 0) {
    $db->beginTransaction();
    $db->query('delete from purchasing_orders where id=' . $id);
    $productIds = [];
    foreach ($order->items as $item) {
      update_product_quantity($item->productId);
      $productIds[] = $item->productId;
    }
    
    update_product_cost($productIds);
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Pembelian #' . format_purchasing_order_code($id) . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
}

render('pos/purchasing-order/editor', [
  'order' => $order
]);
