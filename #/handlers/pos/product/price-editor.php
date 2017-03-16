<?php

require_once CORELIB_PATH . '/Product.php';

$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$productId = (int)(isset($_REQUEST['productId']) ? $_REQUEST['productId'] : 0);
$price = null;
$errors = [];

if (!$id) {
  ensure_current_user_can('add-product-price');
  $price = new ProductPrice();
  $price->productId = $productId;
}
else {
  ensure_current_user_can('edit-product-price');
  $price = $db->query('select * from product_prices where id=' . $id)->fetchObject(ProductPrice::class);
}

$product = $db->query('select * from products where id=' . $price->productId)->fetchObject();
if (!$product) {
  header('Location: ./');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'];
  
  if ($action == 'delete') {
    ensure_current_user_can('delete-product-price');
    
    $db->query('delete from product_prices where id=' . $price->id);
    $_SESSION['FLASH_MESSAGE'] = "Harga telah dihapus.";
    header('Location: ./editor?id=' . $price->productId);
    exit;
  }
  
  if ($action == 'save') {
    $quantityText = trim(isset($_POST['quantity']) ? (string)$_POST['quantity'] : '');

    if (str_starts_with($quantityText, '>=')) {
      $price->quantityMin = trim(str_replace('>=', '', str_replace('.', '', $quantityText)));
    }
    else {
      $quantityArray = explode('-', str_replace('.', '', $quantityText));
      $price->quantityMin = (int)trim($quantityArray[0]);
      $price->quantityMax = (int)trim($quantityArray[1]);
    }
    
    for ($i = 1; $i <= 3; $i++) {
      $priceArray = explode('-', str_replace('.', '', trim((string)$_POST['price' . $i])));
      
      $min = $max = 0;
      if (count($priceArray) == 0) {
        continue;
      }
      else if (count($priceArray) == 1) {
        $min = $max = (int)trim($priceArray[0]);
      }
      else if (count($priceArray) == 2) {
        $min = (int)trim($priceArray[0]);
        $max = (int)trim($priceArray[1]);
      }
      
      $price->{'price'.$i.'Min'} = $min;
      $price->{'price'.$i.'Max'} = $max;
    }
    
    if (!$price->id) {
      $q = $db->prepare('insert into product_prices'
        . ' ( productId, quantityMin, quantityMax, price1Min, price1Max, price2Min, price2Max, price3Min, price3Max)'
        . ' values'
        . ' (:productId,:quantityMin,:quantityMax,:price1Min,:price1Max,:price2Min,:price2Max,:price3Min,:price3Max)');
      $q->bindValue(':productId', $price->productId);
    }
    else {
      $q = $db->prepare('update product_prices set'
        . ' quantityMin=:quantityMin, quantityMax=:quantityMax,'
        . ' price1Min=:price1Min, price1Max=:price1Max,'
        . ' price2Min=:price2Min, price2Max=:price2Max,'
        . ' price3Min=:price3Min, price3Max=:price3Max'
        . ' where id=:id');
      $q->bindValue(':id', $price->id);
    }
    $q->bindValue(':quantityMin', $price->quantityMin);
    $q->bindValue(':quantityMax', $price->quantityMax);
    $q->bindValue(':price1Min', $price->price1Min);
    $q->bindValue(':price1Max', $price->price1Max);
    $q->bindValue(':price2Min', $price->price2Min);
    $q->bindValue(':price2Max', $price->price2Max);
    $q->bindValue(':price3Min', $price->price3Min);
    $q->bindValue(':price3Max', $price->price3Max);
    $q->execute();
    
    $_SESSION['FLASH_MESSAGE'] = "Harga telah disimpan.";
    header('Location: ./editor?id=' . $price->productId);
    exit;
  }
}

render('pos/product/price-editor', [
  'price'   => $price,
  'product' => $product,
  'errors'  => $errors,
]);
