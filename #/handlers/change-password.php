<?php

$errors = [];
$password0 = $password1 = $password2 = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password0 = isset($_POST['password0']) ? (string)$_POST['password0'] : '';
  $password1 = isset($_POST['password1']) ? (string)$_POST['password1'] : '';
  $password2 = isset($_POST['password2']) ? (string)$_POST['password2'] : '';
  
  if (empty($password0)) {
    $errors['password0'] = 'Kata sandi lama harus diisi.';
  }
  else if (encrypt_password($password0) != get_current_user_password()) {
    $errors['password0'] = 'Kata sandi lama salah.';
  }
  
  if (empty($password1)) {
    $errors['password1'] = 'Masukkan kata sandi baru anda.';
  }
  
  if (empty($password2)) {
    $errors['password2'] = 'Silahkan konfirmasi kata sandi baru anda.';
  }
  
  if (empty($errors['password1']) && empty($errors['password2']) && $password1 !== $password2) {
    $errors['password2'] = 'Kata sandi yang anda konfirmasi salah.';
  }
  
  if (empty($errors)) {
    $q = $db->prepare('update users set password=? where id=?');
    $q->bindValue(1, encrypt_password($password1));
    $q->bindValue(2, $_SESSION['CURRENT_USER']->id);
    $q->execute();
    
    $_SESSION['FLASH_MESSAGE'] = 'Kata sandi berhasil diperbarui.';
    header('Location: ./account');
    exit;
  }
}

render('layout', [
  'title'   => 'Ganti Kata Sandi',
  'sidenav' => render('sidenav', true),
  'content' => render('change-password', [
    'errors' => $errors
  ], true),
]);
