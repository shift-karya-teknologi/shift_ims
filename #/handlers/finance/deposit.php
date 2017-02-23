<?php

$step     = 1;
$errors   = [];
$amount   = $actualAmount = 0;
$password = '';
$dateTime = new DateTime();

// get the sum of transaction of this day
$amount = finance_get_sum_amount(1, new DateTime());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
  
  if ($step === 1) {
    $dateTime = $_POST['datetime'];
    $step = 2;
  }
  else if ($step === 2) {
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if (empty($password)) {
      $errors['password'] = 'Kata sandi operator harus diisi.';
    }
    else if (encrypt_password($password) != get_current_user_password()) {
      $errors['password'] = 'Kata sandi operator salah.';
    }
  }
}

render('layout', [
  'title'   => 'Setoran Kas',
  'sidenav' => render('finance/sidenav', true),
  'content' => render('finance/deposit', [
    'errors' => $errors,
    'amount' => $amount,
    'datetime' => $datetime,
    'actualAmount' => $actualAmount,
    'password' => $password,
    'step' => $step,
  ], true),
]);
