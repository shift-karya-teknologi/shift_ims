<?php

ensure_current_user_can('open-report-app');

class Item {
  public $total = 0;
  public $name = '';
  public $items = [];
  
  public function __construct($name = '', $total = 0) {
    $this->name = $name;
    $this->total = $total;
  }
}

$asset = new Item('Aset');
$asset->items[0] = new Item('Kas');
$q = $db->query('select name, balance from finance_accounts where active=1');
while ($r = $q->fetchObject()) {
  $account = new Item($r->name, $r->balance);
  $asset->items[0]->total += $r->balance;
  $asset->items[0]->items[] = $account;
  $asset->total += $r->balance;
}

$asset->items[] = $stock = new Item('Persediaan');
$stock->items[] = new Item('Stok Toko', (int)$db->query('select sum(quantity*cost) from products')->fetchColumn());
$stock->total += $stock->items[count($stock->items) - 1]->total;
$stock->items[] = new Item('Stok di Pembelian', (int)$db->query('select sum(totalCost) from purchasing_orders where status=0')->fetchColumn());
$stock->total += $stock->items[count($stock->items) - 1]->total;
$stock->items[] = new Item('Saldo MultiPayment', (int)$db->query('select sum(balance) from multipayment_accounts where active=1')->fetchColumn());
$stock->total += $stock->items[count($stock->items) - 1]->total;

$asset->total += $asset->items[count($asset->items) - 1]->total;

$debt = new Item('Piutang');
$debt->items[] = new Item('Penjualan Kredit', (int)$db->query('select sum(balance-administrationCost) from credit_accounts')->fetchColumn());
$debt->total += $debt->items[count($debt->items) - 1]->total;
$debt->items[] = new Item('Piutang Perorangan', (int)$db->query('select sum(balance) from debts_accounts where balance > 0')->fetchColumn());
$debt->total += $debt->items[count($debt->items) - 1]->total;

$asset->items[] = $debt;
$asset->total += $asset->items[count($asset->items) - 1]->total;

$asset->items[] = new Item('Aset Warnet', 6000000 * 16);
$asset->total += $asset->items[count($asset->items) - 1]->total;
$asset->items[] = new Item('Aset Toko', 40000000);
$asset->total += $asset->items[count($asset->items) - 1]->total;

$liability = new Item('Kewajiban');
$liability->items[] = new Item('Utang Perorangan', (int)$db->query('select sum(balance) from debts_accounts where balance < 0 and type=0')->fetchColumn());
$liability->total += $liability->items[count($liability->items) - 1]->total;
$liability->items[] = new Item('Utang Bank', (int)$db->query('select sum(balance) from debts_accounts where balance < 0 and type=1')->fetchColumn());
$liability->total += $liability->items[count($liability->items) - 1]->total;

$equity = new Item('Modal', $asset->total + $liability->total);

$data = [
  'asset' => $asset,
  'liability' => $liability,
  'equity' => $equity,
];

render('report/layout', [
  'title'   => 'Neraca',
  'sidenav' => render('report/sidenav', true),
  'content' => render('report/balance-sheet', $data, true),
]);