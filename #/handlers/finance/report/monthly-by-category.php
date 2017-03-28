<?php

$items = [];
$accountId = 2;
$q = $db->query("select t.*, c.name categoryName from finance_transactions t"
  . " left join finance_transaction_categories c on c.id=t.categoryId"
  . " where t.accountId=$accountId"
  . " and t.amount < 0");

$total = 0;
while ($item = $q->fetchObject()) {
  if (empty($item->categoryName))
    $item->categoryName = 'Tanpa Kategori';
  else if ($item->categoryName == 'Pengeluaran Usaha'
    || $item->categoryName == 'Utang Piutang'
    || $item->categoryName == 'Proyek')
    continue;
  
  if (!isset($items[$item->categoryName]))
    $items[$item->categoryName] = 0;
  
  $items[$item->categoryName] += abs($item->amount);
  $total += abs($item->amount);
}

render('finance/report/monthly-by-category', ['items' => $items, 'date' => strtotime(date('Y-m-d'))]);