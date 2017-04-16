<?php

ensure_current_user_can('view-products');

require_once CORELIB_PATH . '/Product.php';

$offset = isset($_GET['_s']) ? (int)$_GET['_s'] : 0;
$is_ajax = isset($_GET['_x']);

if (!isset($_SESSION['PRODUCT_MANAGER_FILTER'])) $_SESSION['PRODUCT_MANAGER_FILTER'] = [];
if (!isset($_SESSION['PRODUCT_MANAGER_FILTER']['type'])) $_SESSION['PRODUCT_MANAGER_FILTER']['type'] = -1;
if (!isset($_SESSION['PRODUCT_MANAGER_FILTER']['status'])) $_SESSION['PRODUCT_MANAGER_FILTER']['status'] = 1;
if (!isset($_SESSION['PRODUCT_MANAGER_FILTER']['categoryId'])) $_SESSION['PRODUCT_MANAGER_FILTER']['categoryId'] = -1;
  
$filter = [];
$filter['type'] = isset($_GET['type']) ? (int)$_GET['type'] : $_SESSION['PRODUCT_MANAGER_FILTER']['type'];
$filter['status'] = isset($_GET['status']) ? (int)$_GET['status'] : $_SESSION['PRODUCT_MANAGER_FILTER']['status'];
$filter['categoryId'] = isset($_GET['categoryId']) ? (int)$_GET['categoryId'] : $_SESSION['PRODUCT_MANAGER_FILTER']['categoryId'];
$filter['name'] = isset($_GET['name']) ? $_GET['name'] : '';

$_SESSION['PRODUCT_MANAGER_FILTER'] = $filter;

$where = [];
$sql = 'select * from products';
if ($filter['type'] !== -1)
  $where[] = 'type=' . $filter['type'];
if ($filter['status'] !== -1)
  $where[] = 'active=' . $filter['status'];
if ($filter['categoryId'] !== -1) {
  if ($filter['categoryId'] === 0)
    $where[] = 'categoryId is null';
  else
    $where[] = 'categoryId=' . $filter['categoryId'];
}
if (!empty($filter['name'])) {
  $where[] = 'name like :name';
}

if (!empty($where))
  $sql .= ' where ' . implode(' and ', $where);

$sql .= ' order by name asc limit 10 offset ' . $offset;

$products = [];

$productByIds = [];
$q = $db->prepare($sql);
if (!empty($filter['name'])) {
  $q->bindValue(':name', '%' . $filter['name'] . '%');
}
$q->execute();

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

if ($is_ajax) {
  render('pos/product/list', [
    'products' => $products,
  ]);  
}
else {
  $categories = $db->query('select * from product_categories order by name asc')->fetchAll(PDO::FETCH_OBJ);
  //  $productCount = $db->query('select count(0) from products')->fetchColumn();
  render('pos/product/list', [
  //  'productCount' => $productCount,
    'products'   => $products,
    'categories' => $categories,
    'filter'     => $filter
  ]);
}