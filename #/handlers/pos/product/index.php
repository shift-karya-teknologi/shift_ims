<?php

require_once CORELIB_PATH . '/Product.php';

$filter = [];

$filter['type']   = isset($_GET['type'])   ? (int)$_GET['type']   : -1;
$filter['status'] = isset($_GET['status']) ? (int)$_GET['status'] : -1;

$where = [];
$sql = 'select * from products';
if ($filter['type'] !== -1)
  $where[] = 'type=' . $filter['type'];
if ($filter['status'] !== -1)
  $where[] = 'active=' . $filter['status'];
if (!empty($where))
  $sql .= ' where ' . implode(' and ', $where);

$sql .= ' order by name asc';

$products = [];

$productByIds = [];
$q = $db->query($sql);
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

render('pos/product/list', [
  'products' => $products,
  'filter'   => $filter
]);
