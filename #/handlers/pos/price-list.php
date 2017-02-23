<?php

$products = $db->query('select p.id, p.name, "2 bh" stock, "20.000" price'
  . ' from products p'
  . ' where p.active=1'
  . ' order by p.name asc')
  ->fetchAll(PDO::FETCH_OBJ);

render('layout', [
  'title'   => 'Daftar Harga',
  'sidenav' => render('pos/sidenav', true),
  'headnav' => render('pos/price-list--rightnav', true),
  'content' => render('pos/price-list', [
    'products' => $products
  ], true),
]);
