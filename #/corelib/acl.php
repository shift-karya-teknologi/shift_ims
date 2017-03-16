<?php

$__ACL__ = [
  1 => [],
  2 => [
    'view-products', 'add-product', 'edit-product', 'view-product-cost',
    'add-product-price', 'edit-product-price',
    'add-product-uom', 'edit-product-uom',
    
    'view-product-categories', 'add-product-category', 'edit-product-category',
    
    'view-purchasing-orders', 'print-purchasing-order',
    'add-purchasing-order', 'edit-purchasing-order', 'complete-purchasing-order', 'cancel-purchasing-order',
    
    'view-sales-orders', 'view-sales-order', 'add-sales-order', 'edit-sales-order', 'complete-sales-order', 'cancel-sales-order',
    'add-sales-order-item', 'edit-sales-order-item', 'delete-sales-order-item',
    
    'view-multipayment-accounts', 'view-multipayment-transactions',
    
    'view-stock-adjustments', 'add-stock-adjustment', 'edit-stock-adjustment',
  ],
  3 => [
    'view-products',
    
    'add-product-category',
    
    'view-sales-orders', 'view-sales-order', 'add-sales-order', 'edit-sales-order', 'complete-sales-order', 'cancel-sales-order',
    'add-sales-order-item', 'edit-sales-order-item', 'delete-sales-order-item',
    
    'view-multipayment-accounts', 'view-multipayment-transactions',
  ],
  4 => [
    'view-products',
    
    'view-sales-orders', 'view-sales-order', 'add-sales-order', 'edit-sales-order', 'complete-sales-order', 'cancel-sales-order',
    'add-sales-order-item', 'edit-sales-order-item', 'delete-sales-order-item',
    
    'view-multipayment-accounts', 'view-multipayment-transactions',
  ]
];

if ($_SESSION['CURRENT_USER']->groupId == 1) {
  function current_user_can(/*$action*/) { return true; }
  function ensure_current_user_can(/*$doAction*/) {}  
}
else {
  
  $__ACL__ = $__ACL__[$_SESSION['CURRENT_USER']->groupId];

  function current_user_can($action) {
    global $__ACL__;
    return in_array($action, $__ACL__);
  }

  function ensure_current_user_can($doAction) {
    if (!current_user_can($doAction))
      exit(render('error/403'));
  }
}