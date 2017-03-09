<?php

$categories = [];

$q = $db->query('select * from product_categories order by name asc');
while ($category = $q->fetchObject()) {
  $categories[] = $category;
}

render('pos/product-category/list', [
  'categories' => $categories,
]);
