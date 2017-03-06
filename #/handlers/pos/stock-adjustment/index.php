<?php

$status = isset($_GET['status']) ? (int)$_GET['status'] : 0;
$sql = 'select * from stock_adjustments where ';

if ($status <= -1)
  $sql .= '1';
else
  $sql .= 'status=:status';

$q = $db->prepare($sql);

if ($status >= 0) {
  $q->bindValue(':status', $status);
}

$q->execute();

$items = $q->fetchAll(PDO::FETCH_OBJ);

render('layout', [
  'title'   => 'Penyesuaian Stok',
  'headnav' => '
    <a href="./create" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">add</i>
    </a>',
  'sidenav' => render('pos/sidenav', true),
  'content' => render('pos/stock-adjustment/list', [
    'status' => $status,
    'items'  => $items
  ], true),
]);