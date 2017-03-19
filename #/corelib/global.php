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

function format_operational_cost_code($id) {
  return 'OC-' . str_pad($id, 5, '0', STR_PAD_LEFT);
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

function update_sales_order_lastmod($id) {
  global $db;
  $q = $db->prepare('update sales_orders set lastModDateTime=:dateTime, lastModUserId=:userId where id=:id');
  $q->bindValue(':dateTime', date('Y-m-d H:i:s'));
  $q->bindValue(':userId', $_SESSION['CURRENT_USER']->id);
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

function extract_locale_date($date, $sep = '/') {
  $a = explode($sep, $date);
  return [isset($a[0]) ? $a[0] : '00', isset($a[1]) ? $a[1] : '00', isset($a[2]) ? $a[2] : '0000'];
}

function to_mysql_date($date) {
  list($dd, $mm, $yyyy) = extract_locale_date($date);
  if (!checkdate((int)$mm, (int)$dd, (int)$yyyy)) {
    return false;
  }
  return "$yyyy-$mm-$dd";
}

function to_mysql_datetime($datetime) {
  $a = explode(' ', $datetime);
  if (count($a) != 2)
    return false;
  
  list($dd, $mm, $yyyy) = extract_locale_date($a[0]);
  if (!checkdate((int)$mm, (int)$dd, (int)$yyyy)) {
    return false;
  }
  
  if (!preg_match("/(2[0-3]|[01][0-9]):([0-5][0-9])(:[0-5][0-9])?/", $a[1])) {
    return false;
  }
  
  if (strlen($a[1]) == 5)
    $a[1] .= ':00';
  
  return "$yyyy-$mm-$dd $a[1]";
}

function to_locale_date($mysql_date) {
  list($y, $m, $d) = explode('-', $mysql_date);
  return "$d/$m/$y";  
}

function to_locale_datetime($mysql_datetime) {
  list($date, $time) = explode(' ', $mysql_datetime);
  list($y, $m, $d) = explode('-', $date);
  return "$d/$m/$y $time";
}

function from_locale_number($number) {
  return (int)str_replace('.', '', $number);
}


function add_multipayment_transaction($data) {
  global $db;
  $q = $db->prepare('insert into multipayment_transactions'
    . ' ( type, accountId, amount, description, dateTime, userId, salesOrderDetailId)'
    . ' values'
    . ' (:type,:accountId,:amount,:description,:dateTime,:userId,:salesOrderDetailId)');
  $q->bindValue(':type', $data->type);
  $q->bindValue(':accountId', $data->accountId);
  $q->bindValue(':amount', $data->amount);
  $q->bindValue(':description', $data->description);
  $q->bindValue(':dateTime', $data->dateTime);
  $q->bindValue(':userId', $data->userId);
  $q->bindValue(':salesOrderDetailId', $data->salesOrderDetailId ? $data->salesOrderDetailId : null);
  $q->execute();
}

function update_multipayment_account_balance($accountId) {
  global $db;
  $q = $db->prepare('update multipayment_accounts set'
    . ' balance=(select ifnull(sum(amount), 0) from multipayment_transactions where accountId=:id)'
    . ' where id=:id');
  $q->bindValue(':id', $accountId);
  $q->execute();
}


function get_current_user_finance_account_ids() {
  global $db;
    $q = $db->query('select * from finance_account_users where userId=' . $_SESSION['CURRENT_USER']->id);
  $ids = [];
  while ($r = $q->fetchObject())
    $ids[] = $r->accountId;
  return $ids;
}

function get_current_user_finance_accounts() {
  require_once __DIR__ . '/FinanceAccount.php';
  
  global $db;

  $sql = 'select a.* from finance_accounts a';
  if ($_SESSION['CURRENT_USER']->groupId != 1) {
    $ids = get_current_user_finance_account_ids();
    if (empty($ids)) return [];
    $sql .= ' where id in (' . implode(',', $ids) . ')';
  }
  $sql .= ' order by a.name asc';
  
  return $db->query($sql)->fetchAll(PDO::FETCH_CLASS, FinanceAccount::class);
}