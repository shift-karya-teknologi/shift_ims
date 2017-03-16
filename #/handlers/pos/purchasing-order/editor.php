<?php

ensure_current_user_can('edit-purchasing-order');

$_now = date('Y-m-d H:i:s');

require_once CORELIB_PATH . '/Product.php';

function _update_purchasing_order($id, $status, $updateId = null) {
  global $db;
  $q = $db->prepare('update purchasing_orders set'
    . ' status=:status, closeDateTime=:dateTime, lastModDateTime=:dateTime, updateId=:updateId'
    . ' where id=' . $id);
  $q->bindValue(':status', $status);
  $q->bindValue(':dateTime', g('_now'));
  $q->bindValue(':updateId', $updateId);
  $q->execute();
}

function _query_products($productIds) {
  global $db;
  return $db->query(
      "select id, costingMethod, cost, manualCost, averageCost, lastPurchaseCost, quantity"
    . " from products where id in ('" . implode("','", $productIds) . "')");
}

function _update_product_costs($productId, $newCost, $averageCost, $lastPurchaseCost) {
  global $db;
  $db->query("update products set"
  . " cost=$newCost, averageCost=$averageCost, lastPurchaseCost=$lastPurchaseCost"
  . " where id=$productId");
}

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
    ensure_current_user_can('complete-purchasing-order');
    $order->itemsByProductIds = [];
    $db->beginTransaction();
    
    // update stock_updates
    $updateId = add_stock_update(3, $_now);
    
    foreach ($order->items as $item) {
      add_stock_update_detail($updateId, $item->productId, $item->quantity);
      $order->itemsByProductIds[$item->productId] = $item;
    }
    
    // update purchasing_orders
    _update_purchasing_order($order->id, 1, $updateId);
    
    // update products
    $q = _query_products(array_keys($order->itemsByProductIds));
    
    while ($product = $q->fetchObject()) {
      $item = $order->itemsByProductIds[$product->id];
      
      if ($product->costingMethod == Product::ManualCostingMethod)
        $oldCost = $product->manualCost;
      else if ($product->costingMethod == Product::AverageCostingMethod)
        $oldCost = $product->averageCost;
      else if ($product->costingMethod == Product::LastPurchaseCostingMethod)
        $oldCost = $product->lastPurchaseCost;
      
      if ($oldCost == 0)
        $oldCost = $product->manualCost;
      
      $newCost = $item->cost;
      $averageCost = floor((($item->quantity * $item->cost) + ($oldCost * $product->quantity))
                     / ($item->quantity + $product->quantity));
      $lastPurchaseCost = $item->cost;
      
      if ($product->costingMethod == Product::ManualCostingMethod)
        $newCost = $product->manualCost;
      else if ($product->costingMethod == Product::AverageCostingMethod)
        $newCost = $averageCost;
      else if ($product->costingMethod == Product::LastPurchaseCostingMethod)
        $newCost = $lastPurchaseCost;
      
      update_product_quantity($product->id);
      _update_product_costs($product->id, $newCost, $averageCost, $lastPurchaseCost);
    }
    
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Pembelian #' . format_purchasing_order_code($id) . ' telah selesai.';
    header('Location: ./editor?id=' . $id);
    exit;
  }
  else if ($order->status == 0 && $action == 'cancel') {
    ensure_current_user_can('cancel-purchasing-order');
    _update_purchasing_order($order->id, 2);    
    $_SESSION['FLASH_MESSAGE'] = 'Pembelian #' . format_purchasing_order_code($id) . ' telah dibatalkan.';
    header('Location: ./');
    exit;
  }
  else if ($action == 'delete') {
    ensure_current_user_can('delete-purchasing-order');
    $db->beginTransaction();
    $db->query('delete from purchasing_orders where id=' . $id);

    if ($order->status == 1) {
      $order->itemsByProductIds = [];
      foreach ($order->items as $item)
        $order->itemsByProductIds[$item->productId] = $item;

      $q = _query_products(array_keys($order->itemsByProductIds));
      while ($product = $q->fetchObject()) {
        $item = $order->itemsByProductIds[$product->id];

        if ($product->costingMethod == Product::ManualCostingMethod)
          $oldCost = $product->manualCost;
        else if ($product->costingMethod == Product::AverageCostingMethod)
          $oldCost = $product->averageCost;
        else if ($product->costingMethod == Product::LastPurchaseCostingMethod)
          $oldCost = $product->lastPurchaseCost;

        if ($oldCost == 0)
          $oldCost = $product->manualCost;

        $newCost = $item->cost;
        
        $totalCost = ($oldCost * $product->quantity) - ($item->quantity * $item->cost);
        $totalQty  = ($product->quantity - $item->quantity);
        if ($totalCost && $totalQty)
          $averageCost = floor($totalCost / $totalQty);
        else
          $averageCost = $product->manualCost;
        
        $lastPurchaseCost = (int)$db->query(
            'select d.cost'
          . ' from purchasing_order_details d'
          . ' inner join purchasing_orders o on o.id=d.parentId'
          . " where o.status=1 and d.productId=$product->id"
          . ' order by o.closeDateTime desc'
          . ' limit 1')
          ->fetchColumn();
        if ($lastPurchaseCost == 0)
          $lastPurchaseCost = $product->manualCost;
        
        if ($product->costingMethod == Product::ManualCostingMethod)
          $newCost = $product->manualCost;
        else if ($product->costingMethod == Product::AverageCostingMethod)
          $newCost = $averageCost;
        else if ($product->costingMethod == Product::LastPurchaseCostingMethod)
          $newCost = $lastPurchaseCost;
        
        _update_product_costs($product->id, $newCost, $averageCost, $lastPurchaseCost);
      }
    }
    
    if ($order->updateId) {
      delete_stock_update($order->updateId);
      foreach ($order->items as $item) {
        update_product_quantity($item->productId);
      }
    }
    
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Pembelian #' . format_purchasing_order_code($id) . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
}

render('pos/purchasing-order/editor', [
  'order' => $order
]);
