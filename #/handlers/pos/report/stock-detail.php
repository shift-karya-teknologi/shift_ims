<?php

if ($_SESSION['CURRENT_USER']->groupId != 1)
  exit(header('Location: ./'));
  
$products = $db->query('select id, active, name, cost, quantity, uom from products where type=0 order by name asc')
  ->fetchAll(PDO::FETCH_OBJ);
$multiPaymentAccounts = $db->query('select * from multipayment_accounts order by name asc')
  ->fetchAll(PDO::FETCH_OBJ);
$pendingPurchasingProducts = $db->query('select'
  . ' d.quantity, d.cost, p.id, p.name, p.uom'
  . ' from purchasing_order_details d'
  . ' inner join purchasing_orders o on o.id = d.parentId'
  . ' inner join products p on p.id=d.productId'
  . ' where o.status=0')->fetchAll(PDO::FETCH_OBJ);
$pendingSalesProducts = $db->query('select'
  . ' d.quantity, d.cost, p.id, p.name, p.uom'
  . ' from sales_order_details d'
  . ' inner join sales_orders o on o.id = d.parentId'
  . ' inner join products p on p.id=d.productId'
  . ' where (p.type=0 or p.type=101) and o.status=0')->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html>
  <head>
    <?= render('head') ?>
  </head>
  <style>
    body{font-family:"Arial Narrow";font-size:9pt;line-height:normal;}
    .r{text-align:right;}
    table{border-collapse:collapse; margin:0;}
    th,td{border:1px solid #000;padding:2px 5px;}
    section{margin:0 auto;}
    h3{margin:15px 0 5px 0;font-size:15px;font-weight:bold;line-height:normal;}
  </style>
<body>
  <div>
  <h3>Saldo Multi Payment</h3>
  <table>
    <tbody>
      <tr>
        <th>No</th>
        <th>Nama Akun</th>
        <th>Saldo</th>
      </tr>
    </tbody>
    <tbody>
      <?php $grandTotal = 0; $i = 1; ?>
      <?php foreach ($multiPaymentAccounts as $account): ?>
        <?php if (!$account->active && $account->balance <= 0) continue; ?>
        <?php $grandTotal += $account->balance; ?>
        <tr>
          <td class="r"><?= format_number($i++) ?></td>
          <td><?= e($account->name) ?></td>
          <td class="r"><?= format_number($account->balance) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="2">Grand Total</th>
        <th><?= format_number($grandTotal) ?></th>
      </tr>
    </tfoot>
  </table>
  </div>
  <?php if (!empty($pendingPurchasingProducts)): ?>
    <div>
      <h3>Barang dalam Proses Pembelian</h3>
      <table>
        <tbody>
          <tr>
            <th>No.</th>
            <th>Kode</th>
            <th>Nama Produk</th>
            <th colspan="2">Stok</th>
            <th>Modal</th>
            <th>Subtotal</th>
          </tr>
        </tbody>
        <tbody>
          <?php $grandTotal = 0; $i = 1;?>
          <?php foreach ($pendingPurchasingProducts as $product): ?>
            <?php $subTotal = $product->cost * $product->quantity; ?>
            <?php $grandTotal += $subTotal; ?>
            <tr>
              <td class="r"><?= format_number($i++) ?></td>
              <td><?= format_product_code($product->id) ?></td>
              <td><?= e($product->name) ?></td>
              <td class="r"><?= format_number($product->quantity) ?></td>
              <td><?= e($product->uom) ?></td>
              <td class="r"><?= format_number($product->cost) ?></td>
              <td class="r"><?= format_number($subTotal) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="6">Grand Total</th>
            <th><?= format_number($grandTotal) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif ?>
  <?php if (!empty($pendingSalesProducts)): ?>
  <div>
    <h3>Barang dalam Proses Penjualan</h3>
    <table>
      <tbody>
        <tr>
          <th>No.</th>
          <th>Kode</th>
          <th>Nama Produk</th>
          <th colspan="2">Stok</th>
          <th>Modal</th>
          <th>Subtotal</th>
        </tr>
      </tbody>
      <tbody>
        <?php $grandTotal = 0; $i = 1;?>
        <?php foreach ($pendingSalesProducts as $product): ?>
          <?php $subTotal = $product->cost * $product->quantity; ?>
          <?php $grandTotal += $subTotal; ?>
          <tr>
            <td class="r"><?= format_number($i++) ?></td>
            <td><?= format_product_code($product->id) ?></td>
            <td><?= e($product->name) ?></td>
            <td class="r"><?= format_number($product->quantity) ?></td>
            <td><?= e($product->uom) ?></td>
            <td class="r"><?= format_number($product->cost) ?></td>
            <td class="r"><?= format_number($subTotal) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="6">Grand Total</th>
          <th><?= format_number($grandTotal) ?></th>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif ?>
  <div>
  <h3>Stok Inventori</h3>
  <table>
    <tbody>
      <tr>
        <th>No.</th>
        <th>Kode</th>
        <th>Nama Produk</th>
        <th colspan="2">Stok</th>
        <th>Modal</th>
        <th>Subtotal</th>
      </tr>
    </tbody>
    <tbody>
      <?php $grandTotal = 0; $i = 1;?>
      <?php foreach ($products as $product): ?>
        <?php if ($product->quantity <= 0) continue; ?>
        <?php $subTotal = $product->cost * $product->quantity; ?>
        <?php $grandTotal += $subTotal; ?>
        <tr>
          <td class="r"><?= format_number($i++) ?></td>
          <td><?= format_product_code($product->id) ?></td>
          <td><?= e($product->name) ?></td>
          <td class="r"><?= format_number($product->quantity) ?></td>
          <td><?= e($product->uom) ?></td>
          <td class="r"><?= format_number($product->cost) ?></td>
          <td class="r"><?= format_number($subTotal) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="6">Grand Total</th>
        <th><?= format_number($grandTotal) ?></th>
      </tr>
    </tfoot>
  </table>
  </div>
</body>