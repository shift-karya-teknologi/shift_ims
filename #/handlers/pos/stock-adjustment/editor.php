<?php

require_once CORELIB_PATH . '/Product.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

$data = $db->query('select * from stock_adjustments where id=' . $id)->fetch(PDO::FETCH_OBJ);
if (!$data) {
  exit(header('Location: ./'));
}

$q = $db->prepare('select d.*, p.name,'
  . ' (select ifnull(sum(d.quantity), 0)'
  . '  from stock_update_details d'
  . '  inner join stock_updates u on u.id = d.parentId'
  . '  where d.productId=p.id and u.dateTime<?) as stock, p.uom'
  . ' from stock_adjustment_details d'
  . ' inner join products p on p.id = d.productId'
  . ' where parentId=' . $id . ' order by p.name asc');
$q->bindValue(1, $data->dateTime);
$q->execute();

$data->items = [];
$data->itemByIds = [];
while ($item = $q->fetchObject()) {
  $data->items[] = $item;
  $data->itemByIds[$item->id] = $item;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? $_POST['action'] : 'save';
  if ($action == 'save' || $action == 'complete') {
    if (!(isset($_POST['i']) && is_array($_POST['i'])))
      exit;
    
    $qtyArray = (array)$_POST['i'];

    $db->beginTransaction();
    $totalCost = $totalPrice = 0;
    foreach ($data->itemByIds as $id => $item) {
      if (!isset($qtyArray[$id]))
        continue;
      
      $qty = $qtyArray[$id];

      $q = $db->prepare('update stock_adjustment_details'
        . ' set quantity=? where id=?');
      $q->bindValue(1, $qty);
      $q->bindValue(2, $item->id);
      $q->execute();
      
      $balance = $qty - $item->stock;
      
      $totalCost  += $balance * $item->cost;
      $totalPrice += $balance * $item->price;
    }
    
    $q = $db->prepare('update stock_adjustments set totalCost=:totalCost, totalPrice=:totalPrice where id=:id');
    $q->bindValue(':totalCost', $totalCost);
    $q->bindValue(':totalPrice', $totalPrice);
    $q->bindValue(':id', $data->id);
    $q->execute();

    if ($action == 'save') {
      $db->commit();

      $_SESSION['FLASH_MESSAGE'] = 'Penyesuaian stok telah disimpan.';
      header('Location: ?id=' . $data->id);
      exit;
    }

    // action=complete
    $updateId = add_stock_update(0, $data->dateTime);

    // update stock_adjustments
    $q = $db->prepare('update stock_adjustments set status=1, updateId=:updateId where id=:id');
    $q->bindValue(':updateId', $updateId);
    $q->bindValue(':id', $data->id);
    $q->execute();

    foreach ($data->itemByIds as $id => $item) {
      if (!isset($qtyArray[$id]))
        continue;
      
      add_stock_update_detail($updateId, $item->productId, $qtyArray[$id] - $item->stock);
      update_product_quantity($item->productId);
    }
    $db->commit();

    $_SESSION['FLASH_MESSAGE'] = 'Penyesuaian stok #' . format_stock_adjustment_code($data->id) . ' telah selesai.';
    header('Location: ?id=' . $data->id);
    exit;
  }
  else if ($action == 'cancel') {
    $db->query('update stock_adjustments set status=2 where id=' . $data->id);
    $_SESSION['FLASH_MESSAGE'] = 'Penyesuaian stok #' . format_stock_adjustment_code($data->id) . ' telah dibatalkan.';
    header('Location: ./');
    exit;
  }
  else if ($action == 'delete') {
    $db->beginTransaction();
    delete_stock_adjustment($data->id);
    if ($data->updateId) {
      delete_stock_update($data->updateId);
      foreach ($data->items as $item)
        update_product_quantity($item->productId);
    }
    $db->commit();

    $_SESSION['FLASH_MESSAGE'] = 'Penyesuaian stok #' . format_stock_adjustment_code($data->id) . ' telah dihapus.';
    header('Location: ./');
    exit;
  } 
}

render('pos/stock-adjustment/editor', [
  'data' => $data
]);
