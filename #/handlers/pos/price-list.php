<?php

class ProductUom
{
  public $id;
  public $productId;
  public $name;
  public $quantity;
}

class Product
{
  const Stocked = 0;
  const NonStocked = 1;
  const Service = 2;
  const ShiftNetVoucher = 255;
  
  public $id;
  public $type;
  public $name;
  public $quantity;
  public $uom;
  public $uoms = [];
  public $prices = [];
  
  public function stockInfo() {
    if ($this->type == Product::Stocked)
      return format_number($this->quantity) . ' ' . $this->uom;
    return '';
  }
  
  public function priceInfo() {
    $arr = [];
    
    foreach ($this->prices as $item) {
      $str = '';
      if ($item->quantityMin && $item->quantityMax) {
        $str .= format_number($item->quantityMin) . ' - ' . format_number($item->quantityMax);
      }
      else if ($item->quantityMin != 0 && $item->quantityMax == 0) {
        $str .= '>= ' . format_number($item->quantityMin);
      }
      
      $str .= ' ' . $this->uom . ' : ';
      $str .= $this->_priceInfo($item);
      
      $arr[] = $str;
    }
    
    return implode('<br>', $arr);
  }

  private function _priceInfo($item) {
    $arr = [];    
    for ($i = 1; $i <=3; $i++) {
      $min = "price{$i}Min";
      $max = "price{$i}Max";
    
      $str = '';
      if ($item->$min != $item->$max) {
        $str .= format_number($item->$min) . ' - ' . format_number($item->$max);
      }
      else if ($item->$min != 0){
        $str .= format_number($item->$min);
      }
      else
        continue;
      
      $arr[] = $str;
    }
    
    return implode(' / ', $arr);
  }

}

// setup products
$productByIds = [];
$products = [];
$q = $db->query('select p.id, p.type, p.name, p.quantity, p.uom'
  . ' from products p'
  . ' where p.active=1 and p.type <= 200'
  . ' order by p.name asc');

while ($product = $q->fetchObject(Product::class)) {
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
