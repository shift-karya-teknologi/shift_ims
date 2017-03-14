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
    
    'add-product-category',
    'edit-product-category',
    'delete-product-category',
    
    'view-multipayment-accounts',
    'add-multipayment-account',
    'edit-multipayment-account',
    'delete-multipayment-account',
    
    'delete-sales-order',
    
    'delete-purchase-order',
    
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
    
    'add-product-category',
    'edit-product-category',
    
    'view-multipayment-account-list',
  ],
  3 => [
    'add-product-category',
    
    'view-multipayment-accounts',
  ],
  4 => [
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