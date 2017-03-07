<?php

require_once CORELIB_PATH . '/Product.php';

$productByIds = [];
$q = $db->query('select * from products where type <= 200 order by name asc');
while ($product = $q->fetchObject(Product::class)) {
  $products[] = $product;
  $productByIds[$product->id] = $product;
}

$q = $db->query('select * from product_prices');
while ($price = $q->fetchObject()) {
  $productByIds[$price->productId]->prices[] = $price;
  unset($price->id);
  unset($price->productId);
}
unset($productByIds);

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
