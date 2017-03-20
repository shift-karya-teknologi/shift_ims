<?php

if ($_SESSION['CURRENT_USER']->groupId != 1)
  exit(header('Location: ./'));
  
$m = isset($_GET['m']) ? (int)$_GET['m'] : date('m');
$y = isset($_GET['y']) ? (int)$_GET['y'] : date('Y');

// TODO: validasi bulan dan tahun

$m = str_pad($m, 2, '0', STR_PAD_LEFT);

$startDateTime = new DateTime("$y-$m-1 06:00:00");
$endDateTime = clone $startDateTime;
$endDateTime->add(new DateInterval('P1M'));

$q = $db->prepare('select oc.dateTime, oc.description, oc.amount, occ.name categoryName'
  . ' from operational_costs oc'
  . ' inner join operational_cost_categories occ on occ.id=oc.categoryId'
  . ' where (oc.dateTime>=? and oc.dateTime <?)'
  . ' order by occ.name asc');
$q->bindValue(1, $startDateTime->format('Y-m-d H:i:s'));
$q->bindValue(2, $endDateTime->format('Y-m-d H:i:s'));
$q->execute();

$items = [];
while ($item = $q->fetchObject()) {
  if (!isset($items[$item->categoryName]))
    $items[$item->categoryName] = 0;
  
  $items[$item->categoryName] += $item->amount;
}

?>
<html>
  <head>
    <?= render('head') ?>
    <style>
      body {color:#000;background:#ddd;}
      .page {width:21.5cm;height:33cm;margin:1cm auto;background:#fff;}
      .content {padding:1cm 1.5cm 1cm 2cm;}
      h1,h2,h3{margin:5px 0;line-height:1.2em;}
      h1{font-size:15pt;}
      h2{font-size:14pt;}
      h3{font-size:13pt;}
      @media print {
        .page { margin:0; height: auto; width: auto; }
      }
      table {border-collapse: collapse;font-size:12px;margin:15px auto; width:100%;}
      th,td {padding: 3px 5px; border:1px solid #000;}
      .r {text-align:right;}
      .d7 {background:#fee;}
      th{background:#eee;}
    </style>
  </head>
<body>
  <div class="page">
    <div class="content">
      <h1>CV. SHIFT KARYA TEKNOLOGI</h1>
      <h2>REKAPITULASI BIAYA OPERASIONAL BULANAN</h2>
      <h3><?= strtoupper(strftime('%B %Y', strtotime("$y-$m-01"))) ?></h3>
      <table>
        <thead>
          <th>No.</th>
          <th>Kategori</th>
          <th style="width:80px;">Jumlah</th>
        </thead>
        <tbody>
          <?php $total = 0; $i = 1; ?>
          <?php foreach ($items as $categoryName => $subTotal): ?>
            <?php $total += $subTotal; ?>
            <tr>
              <td class="r"><?= format_number($i++) ?></td>
              <td><?= e($categoryName) ?></td>
              <td class="r"><?= format_number($subTotal) ?></td>              
            </tr>
          <?php endforeach ?>
        </tbody>
        <tfoot>
          <th colspan="2">Grand Total</th>
          <th class="r"><?= format_number($total) ?></th>
        </tfoot>
      </table>
      </div>
    </div>
</body>