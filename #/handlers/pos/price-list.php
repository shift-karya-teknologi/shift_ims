<?php

// setup products
$productByIds = [];
$products = [];
$q = $db->query('select p.id, p.type, p.name, p.quantity, p.baseUomId'
  . ' from products p'
  . ' where p.active=1'
  . ' order by p.name asc');

while ($product = $q->fetch(PDO::FETCH_OBJ)) {
  $product->priceInfo = [];
  $product->stockInfo = '';
  $product->uoms = [];
  $product->prices = [];
  $productByIds[$product->id] = $product;
  $products[] = $product;
}

$uomByIds = [];
// setup uoms
$q = $db->query('select * from product_uoms order by productId asc, quantity desc, name asc');
while ($uom = $q->fetch(PDO::FETCH_OBJ)) {
  $productByIds[$uom->productId]->uoms[$uom->id] = $uom;
  $uomByIds[$uom->id] = $uom;
}

// setup prices
$q = $db->query('select *'
  . ' from product_prices'
  . ' order by productId asc, qty0 asc');
while ($price = $q->fetch(PDO::FETCH_OBJ)) {
  $productByIds[$price->productId]->prices[] = $price;
}

foreach ($products as $product) {
  $product->baseUom = $uomByIds[$product->baseUomId];
  
  if ($product->type == 1) {
    $uoms = [];
    foreach ($product->uoms as $uom) {
      $uoms[$uom->quantity] = $uom;
    }
    krsort($uoms);
    
    $stockInfoArray = [];
    $stock = $product->quantity;
    foreach ($uoms as $uom) {
      if ($stock == 0)
        break;
      
      $result = floor($stock / $uom->quantity);
      $stock -= ($result * $uom->quantity);
      $stockInfoArray[] = format_number($result) . ' ' . $uom->name;
    }
    $product->stockInfo = implode(', ', $stockInfoArray);
  }
  
  foreach ($product->prices as $price) {      
    if ($price->qty0 && $price->qty1) {
      $strQty = $price->qty0 . ' - ' . $price->qty1;
    }
    else {
      $strQty = '≥ ' . $price->qty0;
    }
    $strQty .= ' ' . $product->baseUom->name;

    if ($price->type == 0) {
      $strPrice = format_number($price->p1);
    }
    else if ($price->type == 1) {
      $strPrice = format_number($price->p2) . ' - ' . format_number($price->p1);
    }
    else {
      $t = [];
      for ($i = 5; $i >= 1; $i--) {
        $col = 'p' . $i;
        if ($price->{$col})
          $t[] = format_number($price->{$col});
      }
      $strPrice = implode(' / ', $t);
    }

    $product->priceInfo[] = [$strQty, $strPrice];
  }
}

render('layout', [
  'title'   => 'Daftar Harga',
  'sidenav' => render('pos/sidenav', true),
  'headnav' => render('pos/price-list--rightnav', true),
  'content' => render('pos/price-list', [
    'products' => $products
  ], true),
]);
