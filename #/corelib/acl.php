<?php

$__ACL__ = [
  1 => [
    'add-product',
    'edit-product',
    'delete-product',
    'view-product-cost',
    
    'delete-sales-order',
    
    'delete-purchase-order',
    
    'delete-stock-adjustment',
  ],
  2 => [
    'add-product',
    'edit-product',
    'view-product-cost',
  ],
  3 => [
    
  ],
  4 => [
    
  ]
];

$__ACL__ = $__ACL__[$_SESSION['CURRENT_USER']->groupId];

function current_user_can($action) {
  global $__ACL__;
  return in_array($action, $__ACL__);
}
