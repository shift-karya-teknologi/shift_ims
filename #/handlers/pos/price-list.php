<?php

require_once CORELIB_PATH . './Product.php';

// setup products
$productByIds = [];
$products = [];
$q = $db->query('select p.id, p.type, p.name, p.quantity, p.uom'
  . ' from products p'
  . ' where p.active=1 and p.type <= 200'
  . ' order by p.name asc');

while ($product = $q->fetchObject(Product::class)) {
  $product->prices = [];
  $productByIds[$product->id] = $product;
  $products[] = $product;
}

$uomByIds = [];
// setup uoms
$q = $db->query('select * from product_uoms order by productId asc, quantity desc, name asc');
while ($uom = $q->fetchObject(ProductUom::class)) {
  $productByIds[$uom->productId]->uoms[$uom->id] = $uom;
  $uomByIds[$uom->id] = $uom;
}

// setup prices
$q = $db->query('select *'
  . ' from product_prices'
  . ' order by productId asc, quantityMin asc');
while ($price = $q->fetch(PDO::FETCH_OBJ)) {
  $productByIds[$price->productId]->prices[] = $price;
}

render('layout', [
  'title'   => 'Daftar Harga',
  'sidenav' => render('pos/sidenav', true),
  'headnav' => render('pos/price-list--rightnav', true),
  'content' => render('pos/price-list', [
    'products' => $products
  ], true),
]);
