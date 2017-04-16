<?php

$startDateTime = new DateTime(date('Y-04-01 00:00:00'));
$endDateTime = clone $startDateTime;
$endDateTime->add(new DateInterval('P1M'));

$items = [];
$accountId = isset($_GET['accountId']) ? (int)$_GET['accountId'] : 1;

$q = $db->prepare("select t.*, c.name categoryName from finance_transactions t"
  . " left join finance_transaction_categories c on c.id=t.categoryId"
  . " where t.accountId=$accountId"
  . " and (t.dateTime>=? and t.dateTime<=?)"
  . " and t.amount < 0"
  . " order by c.name asc");
$q->bindValue(1, $startDateTime->format("Y-m-d H:i:s"));
$q->bindValue(2, $endDateTime->format("Y-m-d H:i:s"));
$q->execute();

$total = 0;
while ($item = $q->fetchObject()) {
  if (empty($item->categoryName)) {
    //$item->categoryName = 'Tanpa Kategori';
    continue;
  }
  else if ($item->categoryName == 'Pengeluaran Usaha'
    || $item->categoryName == 'Utang Piutang'
    || $item->categoryName == 'Proyek')
    continue;
  
  if (!isset($items[$item->categoryName]))
    $items[$item->categoryName] = 0;
  
  $items[$item->categoryName] += abs($item->amount);
  $total += abs($item->amount);
}

$accounts = $db->query('select * from finance_accounts order by name asc')->fetchAll(PDO::FETCH_OBJ);

render('finance/report/monthly-by-category', [
  'accounts' => $accounts,
  'accountId' => $accountId,
  'items' => $items, 'date' => strtotime(date('Y-m-d'))
]);