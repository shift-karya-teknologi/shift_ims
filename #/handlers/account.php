<?php

$user = $db->query('select * from users where id=' . $_SESSION['CURRENT_USER']->id)
  ->fetch(PDO::FETCH_OBJ);

render('layout', [
  'title'   => 'Akun Saya',
  'sidenav' => render('sidenav', true),
  'content' => render('account', [
    'user' => $user
  ], true),
]);
