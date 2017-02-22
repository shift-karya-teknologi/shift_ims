<?php

// Redirect ke home jika user sudah login
if ($_SESSION['CURRENT_USER']) {
  exit(header('Location: ./'));
}

$username = '';
$password = '';
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = (string)filter_input(INPUT_POST, 'username');
  $password = (string)filter_input(INPUT_POST, 'password');
  
  if (!$username) {
    $errors['username'] = 'Nama pengguna harus diisi.';
  }
  
  if (!$password) {
    $errors['password'] = 'Kata sandi harus diisi.';
  }
  
  if (empty($errors)) {
    $q = $db->prepare('select id, username, password, active, groupId from users where username = ?');
    $q->bindValue(1, $username, PDO::PARAM_STR);
    $q->execute();
    $user = $q->fetch(PDO::FETCH_OBJ);
    
    if (!$user) {
      $errors['username'] = 'Akun pengguna tidak ditemukan.';
    }
    else if (encrypt_password($password) !== $user->password) {
      $errors['password'] = 'Kata sandi yang anda masukkan salah.';
    }
    else if (!$user->active) {
      $errors['username'] = 'Akun pengguna sudah tidak aktif.';
    }
    else {
      unset($user->password);
      unset($user->active);

      $_SESSION['CURRENT_USER'] = $user;

      // TODO: Log user activity
      header('Location: ./');
      exit;
    }
  }
}

render('login', [
  'username' => $username,
  'errors'   => $errors,
]);