<?php

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
  . " where parentId=$order->id"
  . ' order by p.name asc')->fetchAll(PDO::FETCH_OBJ);

render('pos/purchasing-order/print', [
  'order' => $order
]);
