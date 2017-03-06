<?php

require_once CORELIB_PATH . '/Product.php';

$products = $db->query('select * from products where type <= 200 order by name asc')
  ->fetchAll(PDO::FETCH_CLASS, Product::class);

render('layout', [
  'title'   => 'Produk',
  'headnav' => '
    <a href="./editor" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">add</i>
    </a>',
  'sidenav' => render('pos/sidenav', true),
  'content' => render('pos/product/list', [
    'products' => $products
  ], true),
]);
