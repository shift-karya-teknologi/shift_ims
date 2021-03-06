<?php

ensure_current_user_can('edit-sales-order');

require_once CORELIB_PATH . '/Product.php';
require_once CORELIB_PATH . '/FinanceAccount.php';
require_once CORELIB_PATH . '/FinanceTransaction.php';
require_once CORELIB_PATH . '/MultiPaymentTransaction.php';

$now = date('Y-m-d H:i:s');
$id  = (isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0);
$order = $db->query('select o.*, u.username lastModUsername'
  . ' from sales_orders o'
  . ' left join users u on u.id=o.lastModUserId'
  . " where o.id=$id"
  )->fetchObject();

if (!$order) {
  $_SESSION['FLASH_MESSAGE'] = 'Penjualan #' . format_sales_order_code($id) . ' tidak ditemukan.';
  header('Location: ./');
  exit;
}

$order->items = $db->query('select d.*,'
  . ' p.name productName, p.uom productUom, p.type productType'
  . ' from sales_order_details d'
  . ' inner join products p on p.id = d.productId'
  . ' where parentId=' . $order->id)->fetchAll(PDO::FETCH_OBJ);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];
  
  if ($order->status == 0 && $action == 'complete') {
    ensure_current_user_can('complete-sales-order');
    $db->beginTransaction();   
    $updateId = add_stock_update(1, $now);
    foreach ($order->items as $item) {
      if ($item->productType == Product::Stocked) {
        add_stock_update_detail($updateId, $item->productId, -$item->quantity);
        update_product_quantity($item->productId);
      }
      else if ($item->productType == Product::MultiPayment) {
          for ($i = 0; $i < $item->quantity; $i++) {
            $data = new MultiPaymentTransaction();
            $data->type = MultiPaymentTransaction::Sales;
            $data->dateTime = $now;
            $data->userId = $_SESSION['CURRENT_USER']->id;
            $data->accountId = $item->multiPaymentAccountId;
            $data->amount = -$item->cost;
            $data->description = $item->productName;
            $data->salesOrderDetailId = $item->id;
            add_multipayment_transaction($data);
          }
        }
        update_multipayment_account_balance($item->multiPaymentAccountId);
    }
    $q = $db->prepare('update sales_orders set'
      . ' status=1, closeDateTime=:dateTime, lastModDateTime=:dateTime, updateId=:updateId, closeUserId=:userId, lastModUserId=:userId'
      . ' where id=' . $id);
    $q->bindValue(':dateTime', $now);
    $q->bindValue(':updateId', $updateId);
    $q->bindValue(':userId', $_SESSION['CURRENT_USER']->id);
    $q->execute();
    
    $transaction = new FinanceTransaction();
    $transaction->accountId = $cfg['store_account_id'];
    $transaction->type = FinanceTransaction::Income;
    $transaction->amount = $order->totalPrice;
    $transaction->dateTime = $now;
    $transaction->description = "Penjualan #" . format_sales_order_code($order->id);
    $transaction->refType = 'sales-order';
    $transaction->refId = $order->id;
    $transaction->userId = $_SESSION['CURRENT_USER']->id;
    
    FinanceTransaction::save($transaction);
    FinanceAccount::updateBalance($transaction->accountId);
    
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Penjualan #' . format_sales_order_code($id) . ' telah selesai.';
    header('Location: ./editor?id=' . $id);
    exit;
  }
  else if ($order->status == 0 && $action == 'cancel') {
    ensure_current_user_can('cancel-sales-order');
    $db->beginTransaction();
    $q = $db->prepare('update sales_orders set'
      . ' status=2, closeDateTime=:dateTime, lastModDateTime=:dateTime, closeUserId=:userId, lastModUserId=:userId'
      . ' where id=' . $id);
    $q->bindValue(':dateTime', $now);
    $q->bindValue(':userId', $_SESSION['CURRENT_USER']->id);
    $q->execute();
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Penjualan #' . format_sales_order_code($id) . ' telah dibatalkan.';
    header('Location: ./');
    exit;
  }
  else if ($order->status != 0 && $action == 'delete') {
    ensure_current_user_can('delete-sales-order');
    $db->beginTransaction();
    $db->query("delete from sales_orders where id=$order->id");
    if ($order->updateId) {
      delete_stock_update($order->updateId);
      foreach ($order->items as $item) {
        if ($item->productType == Product::Stocked) {
          update_product_quantity($item->productId);
        }
        else if ($item->productType == Product::MultiPayment) {
          update_multipayment_account_balance($item->multiPaymentAccountId);
        }
      }
    }
    if ($order->status == 1) {
      if ($transaction = FinanceTransaction::findByReference('sales-order', $order->id)) {
        FinanceTransaction::delete($transaction->id);
        FinanceAccount::updateBalance($transaction->accountId);
      }
    }
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Penjualan #' . format_sales_order_code($id) . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
}

render('pos/sales-order/editor', [
  'order' => $order
]);
