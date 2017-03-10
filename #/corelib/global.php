<?php

/**
 * Mengenkripsi password
 * @param string $plain Password yang akan dienkripsi
 * @return string Password yang sudah terenkripsi
 */
function encrypt_password($plain) {
  return sha1('$#' . $plain . '1FT');
}

/**
 * Render template 
 * @param string $args[0] Nama template, lokasi relatif dari TEMPLATE_PATH
 * @param array  $args[1] Data yang akan dikirim ke template
 * @param bool   $args[2] Opsi output buffer, set true jika ingin hasil render disimpan ke buffer dan dikembalikan oleh
 *                        fungsi ini
 * @return null|string Apabila parameter ke tiga diset true maka fungsi akan mengembalikan string hasil buffer
 */
function render() {
  if (func_num_args() == 2) {
    if (is_bool(func_get_arg(1))) {
      ob_start();
    }
    else {
      extract(func_get_arg(1));
    }
  }
  else if (func_num_args() == 3 && func_get_arg(2) === true) {
    extract(func_get_arg(1));
    ob_start();
  }
  
  include TEMPLATE_PATH . DIRECTORY_SEPARATOR . func_get_arg(0) . '.phtml';
  
  if (func_num_args() == 2 && func_get_arg(1) === true || func_num_args() == 3 && func_get_arg(2) === true) {
    return ob_get_clean();
  }
}

/**
 * Fungsi untuk mengakses variabel global
 * @param string $name  Nama variabel global.
 * @param mixed $default Nilai default jika variabel tidak ditemukan.
 * @return mixed Nilai variabel atau nilai default jika nama variabel tidak ditemukan.
 */
function g($name, $default = null) {
  return isset($GLOBALS[$name]) ? $GLOBALS[$name] : $default;
}

/**
 * Shorthand untuk escape elemen atau atribut html 
 * @param string $str String yang akan di escape
 * @return string String hasil escape
 */
function e($str) {
  return htmlentities((string)$str, ENT_COMPAT | ENT_HTML5, 'UTF-8', true);
}

function str_starts_with($str, $prefix) {
  return mb_substr($str, 0, mb_strlen($prefix)) === $prefix;
}


function format_number($value, $decimal = 0) {
  return number_format((float)$value, $decimal, ',', '.');
}

function format_decimal($amount, $decimal) {
  return number_format((float)$amount, $decimal, ',', '.');
}

function format_integer($amount) {
  return number_format((float)$amount, 0, ',', '.');
}

function format_money($amount) {
  return 'Rp. ' . format_integer($amount, 0, ',', '.');
}

function format_duration($duration) {
  $a = floor($duration / 60);
  $b = $duration - ($a * 60);
  return str_pad((string)$a, 2, "0", STR_PAD_LEFT) . ':' . str_pad((string)$b, 2, "0", STR_PAD_LEFT);
}

function get_shift_start($datetime) {
  $start = new DateTime($datetime->format('Y-m-d') . '06:00:00');
  if ($datetime->format('H') < 6)
    $start->sub(new DateInterval('P1D'));
  return $start;
}

function get_shift_end($datetime) {
  $end = new DateTime($datetime->format('Y-m-d') . '05:59:59');
  if ($datetime->format('H') > 5)
    $end->add(new DateInterval('P1D'));
  return $end;
}

function get_current_user_password() {
  return g('db')
    ->query('select password from users where id=' . $_SESSION['CURRENT_USER']->id)
    ->fetch(PDO::FETCH_COLUMN);
}

function finance_get_sum_amount($accountId, $dateTime) {
  $q = g('db')->prepare('select sum(amount) from finance_transactions where accountId=?'
    . 'and dateTime<=?');
  $q->bindValue(1, $accountId);
  $q->bindValue(2, $dateTime->format('Y-m-d H:i:s'));
  $q->execute();
  return $q->fetch(PDO::FETCH_COLUMN);
}

function format_product_code($id) {
  return 'P-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}


function format_stock_adjustment_code($id) {
  return 'SA-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function format_sales_order_code($id) {
  return 'SO-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function format_purchasing_order_code($id) {
  return 'PO-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function format_stock_adjustment_status($status) {
  return $status == 0 ? 'Disimpan' : ($status == 1 ? 'Selesai' : 'Dibatalkan');
}

function format_purchasing_order_status($status) {
  return format_stock_adjustment_status($status);
}

function format_sales_order_status($status) {
  return format_stock_adjustment_status($status);
}

function update_product_quantity($productId) {
  global $db;
  $q = $db->prepare('update products set quantity=(select ifnull(sum(quantity), 0) from stock_update_details where productId=?) where id=?');
  $q->bindValue(1, $productId);
  $q->bindValue(2, $productId);
  $q->execute();
}

function add_stock_update($type, $dateTime) {
  global $db;
  $q = $db->prepare('insert into stock_updates'
    . ' ( type, dateTime) '
    . ' values '
    . ' (:type,:dateTime)');
  $q->bindValue(':type', $type);
  $q->bindValue(':dateTime', $dateTime);
  $q->execute();
  
  return $db->lastInsertId();
}

function add_stock_update_detail($parentId, $productId, $quantity) {
  global $db;
  $q = $db->prepare('insert into stock_update_details'
    . ' ( parentId, productId, quantity)'
    . ' values '
    . ' (:parentId,:productId,:quantity)');
  $q->bindValue(':parentId', $parentId);
  $q->bindValue(':productId', $productId);
  $q->bindValue(':quantity', $quantity);
  $q->execute();
}

function delete_stock_adjustment($id) {
  global $db;
  $db->query('delete from stock_adjustment_details where parentId=' . $id);
  $db->query('delete from stock_adjustments where id=' . $id);
}

function delete_stock_update($id) {
  global $db;
  $db->query('delete from stock_update_details where parentId=' . $id);
  $db->query('delete from stock_updates where id=' . $id);
}

function update_sales_order_subtotal($id) {
  global $db;
  $q = $db->prepare('update sales_orders set totalCost=(select ifnull(sum(subtotalCost), 0) from sales_order_details where parentId=:id),'
    . ' totalPrice=(select ifnull(sum(subtotalPrice), 0) from sales_order_details where parentId=:id)'
    . ' where id=:id');
  $q->bindValue(':id', $id);
  $q->execute();
}

function update_purchasing_order_subtotal($id) {
  global $db;
  $q = $db->prepare('update purchasing_orders set totalCost=(select ifnull(sum(subtotalCost), 0) from purchasing_order_details where parentId=:id)'
    . ' where id=:id');
  $q->bindValue(':id', $id);
  $q->execute();
}

function update_product_cost($productIds) {
  global $db;
  
  $q = $db->query("select id, costingMethod, cost, manualCost, averageCost, lastPurchaseCost from products"
    . " where id in ('" . implode("','", $productIds) . "')");

  while ($product = $q->fetchObject()) {
    $averageCost = (int)$db->query("select sum(d.subtotalCost) / sum(d.quantity)"
        . " from purchasing_order_details d"
        . " inner join purchasing_orders o on o.id = d.parentId"
        . " where o.status=1 and d.productId=$product->id")->fetch(PDO::FETCH_COLUMN);
    $lastPurchaseCost = (int)$db->query("select d.cost"
        . " from purchasing_order_details d"
        . " inner join purchasing_orders o on o.id = d.parentId"
        . " where o.status=1 and d.productId=$product->id order by o.closeDateTime desc limit 1")->fetch(PDO::FETCH_COLUMN);
    $cost = 0;

    if ($product->costingMethod == Product::ManualCostingMethod)
      $cost = $product->manualCost;
    else if ($product->costingMethod == Product::AverageCostingMethod)
      $cost = $averageCost;
    else if ($product->costingMethod == Product::LastPurchaseCostingMethod)
      $cost = $lastPurchaseCost;
    
    // untuk restore harga
    if ($cost == 0)
      $cost = $product->manualCost;
    if ($averageCost == 0)
      $averageCost = $cost;
    if ($lastPurchaseCost == 0)
      $lastPurchaseCost = $cost;
    
    $db->query("update products set"
      . " cost=$cost, averageCost=$averageCost, lastPurchaseCost=$lastPurchaseCost"
      . " where id=$product->id");
  }
}