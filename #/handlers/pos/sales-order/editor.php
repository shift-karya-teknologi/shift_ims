<?php

$now = date('Y-m-d H:i:s');
$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$order = $db->query('select * from sales_orders where id=' . $id)->fetchObject();
if (!$order) {
  if ($id) {
    $_SESSION['FLASH_MESSAGE'] = 'Penjualan #' . format_sales_order_code($id) . ' tidak ditemukan.';
  }
  header('Location: ./');
  exit;
}

$order->items = $db->query('select d.*,'
  . ' p.name productName, p.uom productUom'
  . ' from sales_order_details d'
  . ' inner join products p on p.id = d.productId'
  . ' where parentId=' . $order->id)->fetchAll(PDO::FETCH_OBJ);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];
  
  if ($order->status == 0 && $action == 'complete') {
    $db->beginTransaction();
    
    $updateId = add_stock_update(1, $now);
    
    foreach ($order->items as $item) {
      add_stock_update_detail($updateId, $item->productId, -$item->quantity);
      update_product_quantity($item->productId);
    }
    
    $q = $db->prepare('update sales_orders set'
      . ' status=1, closeDateTime=:dateTime, lastModDateTime=:dateTime, updateId=:updateId'
      . ' where id=' . $id);
    $q->bindValue(':dateTime', $now);
    $q->bindValue(':updateId', $updateId);
    $q->execute();
    
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Penjualan #' . format_sales_order_code($id) . ' telah selesai.';
    header('Location: ./editor?id=' . $id);
    exit;
  }
  else if ($order->status == 0 && $action == 'cancel') {
    $db->beginTransaction();
    $q = $db->prepare('update sales_orders set'
      . ' status=2, closeDateTime=:dateTime, lastModDateTime=:dateTime'
      . ' where id=' . $id);
    $q->bindValue(':dateTime', $now);
    $q->execute();
    
    $db->commit();
    $_SESSION['FLASH_MESSAGE'] = 'Penjualan #' . format_sales_order_code($id) . ' telah dibatalkan.';
    header('Location: ./');
    exit;
  }
  else if ($action == 'delete' && $order->status == 2) {
    $db->query('delete from sales_orders where id=' . $id);
    $_SESSION['FLASH_MESSAGE'] = 'Penjualan #' . format_sales_order_code($id) . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
}

render('layout', [
  'title'   => format_sales_order_code($order->id),
  'headnav' => ($order->status == 0 ? '
    <a href="./item-editor?pid=' . $order->id . '" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">add</i>
    </a>' : '')
  .
    '<a href="./" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">close</i>
    </a>'
  ,
  'sidenav' => render('pos/sidenav', true),
  'content' => render('pos/sales-order/editor', [
    'order' => $order
  ], true),
]);
