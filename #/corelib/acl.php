<?php

$__ACL__ = [
  1 => [
    'add-product',
    'edit-product',
    'delete-product',
    'view-product-cost',
    
    'add-product-price',
    'edit-product-price',
    'delete-product-price',
    
    'add-product-uom',
    'edit-product-uom',
    'delete-product-uom',
    
    'view-product-categories',
    'add-product-category',
    'edit-product-category',
    'delete-product-category',
    
    'view-multipayment-accounts',
    'add-multipayment-account',
    'edit-multipayment-account',
    'delete-multipayment-account',
    'topup-multipayment-account',
    'adjust-multipayment-account',
    'view-multipayment-transactions',
    'add-multipayment-transaction',
    'edit-multipayment-transaction',
    'delete-multipayment-transaction',
    
    'view-stock-adjustments',
    
    'view-sales-orders',
    'view-sales-order',
    'add-sales-order',
    'edit-sales-order',
    'complete-sales-order',
    'cancel-sales-order',
    'delete-sales-order',
    
    'add-sales-order-item',
    'edit-sales-order-item',
    'delete-sales-order-item',
    
    'view-purchasing-orders',
    'add-purchasing-order',
    'edit-purchasing-order',
    'print-purchasing-order',
    'complete-purchasing-order',
    'cancel-purchasing-order',
    'delete-purchasing-order',
    
    'delete-stock-adjustment',
  ],
  2 => [
    'add-product',
    'edit-product',
    'view-product-cost',
    
    'add-product-price',
    'edit-product-price',
    
    'add-product-uom',
    'edit-product-uom',
    
    'view-product-categories',
    'add-product-category',
    'edit-product-category',
    
    'view-purchasing-orders',
    'add-purchasing-order',
    'edit-purchasing-order',
    'print-purchasing-order',
    'complete-purchasing-order',
    'cancel-purchasing-order',
    
    'view-sales-orders',
    'view-sales-order',
    'add-sales-order',
    'edit-sales-order',
    'complete-sales-order',
    'cancel-sales-order',
    
    'add-sales-order-item',
    'edit-sales-order-item',
    'delete-sales-order-item',
    
    'view-multipayment-accounts',
    'view-multipayment-transactions',
    
    'view-stock-adjustments',
  ],
  3 => [
    'add-product-category',
    
    'view-sales-orders',
    'view-sales-order',
    'add-sales-order',
    'edit-sales-order',
    'complete-sales-order',
    'cancel-sales-order',
    
    'add-sales-order-item',
    'edit-sales-order-item',
    'delete-sales-order-item',
    
    'view-multipayment-accounts',
  ],
  4 => [
    'view-sales-orders',
    'view-sales-order',
    'add-sales-order',
    'edit-sales-order',
    'complete-sales-order',
    'cancel-sales-order',
    
    'add-sales-order-item',
    'edit-sales-order-item',
    'delete-sales-order-item',
    
    'view-multipayment-accounts',
  ]
];

$__ACL__ = $__ACL__[$_SESSION['CURRENT_USER']->groupId];

function current_user_can($action) {
  global $__ACL__;
  return in_array($action, $__ACL__);
}

function ensure_current_user_can($doAction) {
  if (!current_user_can($doAction))
    exit(render('error/403'));
}