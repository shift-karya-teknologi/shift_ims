<?php

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!($id && $member = $db->query("select id, username from shiftnet_members where id=$id")
    ->fetch(PDO::FETCH_OBJ))) {
  header('Location: ./');
  exit;
}

$errors = [];
$password1 = $password2 = $operatorPassword = '';
$operator = clone $_SESSION['CURRENT_USER'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password1 = isset($_POST['password1']) ? (string)$_POST['password1'] : '';
  $password2 = isset($_POST['password2']) ? (string)$_POST['password2'] : '';
  $operatorPassword = isset($_POST['operatorPassword']) ? (string)$_POST['operatorPassword'] : '';
  
  if (mb_strlen($password1) < 6) {
    $errors['password1'] = 'Kata sandi minimal 6 karakter.';
  }
  
  if (empty($password2)) {
    $errors['password2'] = 'Ulangi kata sandi baru.';
  }
  
  if (empty($errors['password1']) && empty($errors['password2']) && $password1 !== $password2) {
    $errors['password2'] = 'Kata sandi yang anda konfirmasi salah.';
  }
  
  if (empty($operatorPassword)) {
    $errors['operatorPassword'] = 'Kata sandi operator harus diisi.';
  }
  else if (encrypt_password ($operatorPassword) != get_current_user_password()) {
    $errors['operatorPassword'] = 'Kata sandi operator salah.';
  }
  
  if (empty($errors)) {
    $db->beginTransaction();
    $q = $db->prepare('update shiftnet_members set password=? where id=?');
    $q->bindValue(1, sha1($password1));
    $q->bindValue(2, $member->id);
    $q->execute();
    
    if ($q->rowCount() > 0) {
      $q = $db->prepare('update shiftnet_members set resetPasswordOperatorId=?, resetPasswordDateTime=? where id=?');
      $q->bindValue(1, $_SESSION['CURRENT_USER']->id);
      $q->bindValue(2, date('Y-m-d H:i:s'));
      $q->bindValue(3, $member->id);
      $q->execute();
    }
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Kata sandi member telah diatur ulang.';
    header('Location: ./view?id=' . $member->id);
    exit;
  }
}

render('layout', [
  'title'    => 'Atur Ulang Sandi Member',
  'sidenav'  => render('shiftnet/sidenav', true),
  'content'  => render('shiftnet/member/reset-password', [
    'member' => $member,
    'operator' => $_SESSION['CURRENT_USER']->username,
    'operatorPassword' => $operatorPassword,
    'password1' => $password1,
    'password2' => $password2,
    'errors' => $errors,
  ], true)
]);