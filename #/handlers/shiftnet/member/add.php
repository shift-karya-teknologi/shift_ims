<?php

$errors   = [];
$username = $password1 = $password2 = '';
$active   = 1;

$disallowed_usernames = [
  'admin', 'administrator',
  'user', 'operator', 'tester',
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username  = strtolower(filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_STRING));
  $password1 = isset($_POST['password1']) ? (string)$_POST['password1'] : '';
  $password2 = isset($_POST['password2']) ? (string)$_POST['password2'] : '';
  $active    = isset($_POST['active']) && (int)$_POST['active'] === 1;
  
  $lc_username = mb_strtolower($username);
  if (mb_strlen($username) < 3) {
    $errors['username'] = 'Nama pengguna harus diisi, minimal 3 karakter.';
  }
  else if (!preg_match('/^[a-zA-Z]+[a-zA-Z0-9_]*$/', $username)) {
    $errors['username'] = 'Nama pengguna harus diawali alfabet dan boleh diikuti huruf, angka atau underscore (_).';
  }
  else if (in_array ($lc_username, $disallowed_usernames)) {
    $errors['username'] = 'Nama pengguna ' . $lc_username . ' tidak diizinkan.';
  }
  else {
    $q = $db->prepare('select count(0) from shiftnet_members where username=?');
    $q->bindValue(1, $username);
    $q->execute();
    if ($q->fetchColumn(0) > 0) {
      $errors['username'] = 'Nama pengguna <b>' . $username. '</b> sudah digunakan.';
    }
  }
  
  if (mb_strlen($password1) < 6) {
    $errors['password1'] = 'Kata sandi harus diisi, minimal 6 karakter.';
  }
  else if (mb_strlen($password2) === 0) {
    $errors['password2'] = 'Silahkan konfirmasi kata sandi.';
  }
  else if ($password1 !== $password2) {
    $errors['password2'] = 'Kata sandi yang anda konfirmasi salah.';
  }
  
  if (empty($errors)) {
    $q = $db->prepare('insert into shiftnet_members('
      . ' username'
      . ',password'
      . ',active'
      . ',registrationOperatorId'
      . ',registrationDateTime'
      . ')values(?,?,?,?,?)');
    $now = date('Y-m-d H:i:s');
    $q->bindValue(1, $username);
    $q->bindValue(2, sha1($password));
    $q->bindValue(3, $active);
    $q->bindValue(4, $_SESSION['CURRENT_USER']->id);
    $q->bindValue(5, $now);
    $q->execute();
   
    $_SESSION['FLASH_MESSAGE'] = 'Akun ' . $username . ' telah terdaftar sebagai member ShiftNet.';
    header('Location: ./');
    exit;
  }
}

render('layout', [
  'title'    => 'Tambah Member',
  'sidenav'  => render('shiftnet/sidenav', true),
  'content'  => render('shiftnet/member/add', [
    'username' => $username,
    'active'   => $active,
    'errors'   => $errors,
  ], true)
]);