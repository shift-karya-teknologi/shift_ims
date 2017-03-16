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

$q = $db->prepare('select closeDateTime, totalCost, totalPrice'
  . ' from sales_orders'
  . ' where status=1 and (closeDateTime>=? and closeDateTime <?)'
  . ' order by closeDateTime asc');
$q->bindValue(1, $startDateTime->format('Y-m-d H:i:s'));
$q->bindValue(2, $endDateTime->format('Y-m-d H:i:s'));
$q->execute();

$itemByDatesAndShifts = [];
for ($i = 1; $i <= $startDateTime->format('t'); $i++) {
  $d = str_pad($i, 2, '0', STR_PAD_LEFT);
  $itemByDatesAndShifts["$y-$m-$d"] = [1 => 0, 2 => 0];
}

$grandTotal = 0;
$total = [1 => 0, 2 => 0];

while ($order = $q->fetchObject()) {
  $order->closeDateTime = new DateTime($order->closeDateTime);
  $hour = $order->closeDateTime->format('H');
  if ($hour >= 6 && $hour < 18) {
    $date = $order->closeDateTime->format('Y-m-d');
    $shift = 1;
  }
  else {
    if ($order->closeDateTime->format('H') < 6) {
      $dateTime = clone $order->closeDateTime;
      $dateTime->sub(new DateInterval('P1D'));
      $date = $dateTime->format('Y-m-d');
    }
    
    $shift = 2;
  }
  $itemByDatesAndShifts[$date][$shift] += $order->totalPrice;
  $total[$shift] += $order->totalPrice;
  $grandTotal += $order->totalPrice;
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
      <h2>LAPORAN PENJUALAN BULANAN</h2>
      <h3><?= strtoupper(strftime('%B %Y', strtotime("$y-$m-01"))) ?></h3>
      <table>
        <thead>
          <th style="width:25px;">Tgl</th>
          <th style="width:50px;">Hari</th>
          <th style="width:80px;">Siang</th>
          <th style="width:80px;">Malam</th>
          <th style="width:80px;">Subtotal</th>
          <th>Catatan</th>
        </thead>
        <tbody>
      <?php for ($i = 1; $i <= $startDateTime->format('t'); $i++): ?>
        <?php
          $d = str_pad($i, 2, '0', STR_PAD_LEFT);
          $time = strtotime("$y-$m-$d");
        ?>
        <tr class="d<?= date('N', $time) ?>">
          <td class="r"><?= $i ?></td>
          <td><?= strftime('%A', $time) ?></td>
          <td class="r"><?= format_number($itemByDatesAndShifts["$y-$m-$d"][1]) ?></td>
          <td class="r"><?= format_number($itemByDatesAndShifts["$y-$m-$d"][2]) ?></td>
          <td class="r"><?= format_number($itemByDatesAndShifts["$y-$m-$d"][1] + $itemByDatesAndShifts["$y-$m-$d"][2]) ?></td>
          <td></td>
        </tr>
      <?php endfor ?>
        </tbody>
        <tfoot>
          <th colspan="2" class="r">Grand Total</th>
          <th class="r"><?= format_number($total[1]) ?></th>
          <th class="r"><?= format_number($total[2]) ?></th>
          <th class="r"><?= format_number($grandTotal) ?></th>
          <th></th>
        </tfoot>
      </table>
      </div>
    </div>
</body>