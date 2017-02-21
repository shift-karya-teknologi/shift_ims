<?php

/**
 * Mengenkripsi password
 * @param string $plain Password yang akan dienkripsi
 * @return string Password yang sudah terenkripsi
 */
function encrypt_password(string $plain) {
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
function g(string $name, $default = null) {
  return isset($GLOBALS[$name]) ? $GLOBALS[$name] : $default;
}

/**
 * Shorthand untuk escape elemen atau atribut html 
 * @param string $str String yang akan di escape
 * @return string String hasil escape
 */
function e(string $str) {
  return htmlentities($str, ENT_COMPAT | ENT_HTML5, 'UTF-8', true);
}

function str_starts_with(string $str, string $prefix) {
  return mb_substr($str, 0, mb_strlen($prefix)) === $prefix;
}


function format_number($value, int $decimal = 0) {
  return number_format($value, $decimal, ',', '.');
}

function format_decimal($amount, $decimal) {
  return number_format($amount, $decimal, ',', '.');
}

function format_integer($amount) {
  return number_format($amount, 0, ',', '.');
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