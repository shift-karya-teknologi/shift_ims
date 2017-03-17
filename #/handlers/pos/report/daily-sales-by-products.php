<?php

require CORELIB_PATH . '/Product.php';

class Shift
{
  public $itemByTypes = [];
}

$shifts = [1 => new Shift(), 2 => new Shift()];

if ($_SESSION['CURRENT_USER']->groupId != 1)
  exit(header('Location: ./'));
  
$date = isset($_GET['date']) ? (string)$_GET['date'] : date('Y-m-d');
$dateTime = new DateTime($date . ' ' . date('H:i:s'));
if ($dateTime->format('H') < 6)
  $dateTime->sub(new DateInterval('P1D'));
$date = $dateTime->format('Y-m-d');

$startDateTime = new DateTime("$date 06:00:00");
$endDateTime = clone $startDateTime;
$endDateTime->add(new DateInterval('P1D'));

$q = $db->prepare('select d.*,'
  . ' p.type productType, p.name productName, p.uom uom,'
  . ' o.closeDateTime'
  . ' from sales_order_details d'
  . ' inner join sales_orders o on o.id=d.parentId'
  . ' inner join products p on p.id=d.productId'
  . ' where o.status=1 and (o.closeDateTime>=? and o.closeDateTime<?)'
  . ' order by productName asc'
);

$q->bindValue(1, $startDateTime->format('Y-m-d H:i:s'));
$q->bindValue(2, $endDateTime->format('Y-m-d H:i:s'));
$q->execute();

while ($record = $q->fetchObject()) {
  $dateTime = new DateTime($record->closeDateTime);
  $hour = $dateTime->format('H');
  $shift = $hour >= 6 && $hour < 18 ? 1 : 2;
  $shift = $shifts[$shift];
  
  if (!isset($shift->itemByTypes[$record->productType]))
    $shift->itemByTypes[$record->productType] = [];
  
  if (!isset($shift->itemByTypes[$record->productType][$record->productId]))
    $shift->itemByTypes[$record->productType][$record->productId] = [
      'name' => $record->productName,
      'uom' => $record->uom,
      'quantity' => 0,
      'price' => 0,
      'profit' => 0,
    ];
  
  $shift->itemByTypes[$record->productType][$record->productId]['quantity'] += $record->quantity;
  $shift->itemByTypes[$record->productType][$record->productId]['price'] += $record->subtotalPrice;
  $shift->itemByTypes[$record->productType][$record->productId]['profit'] += ($record->subtotalPrice - $record->subtotalCost);
}

?>
<html>
  <head>
    <?= render('head') ?>
    <style>
      body {color:#000;background:#ddd;font-family:"Arial Narrow";font-size:9px;}
      .page {width:21.5cm;height:33cm;margin:1cm auto;background:#fff;}
      .content {padding:1cm 1.5cm 1cm 2cm;}
      h1,h2,h3{margin:5px 0;line-height:1.2em;}
      h1{font-size:11pt;}
      h2{font-size:14pt;}
      h3{font-size:11pt;}
      h5{font-size:10pt;margin:20px 0 6px;}
      @media print {
        .page { margin: 0; height: auto; width: auto; }
      }
      table { border-collapse: collapse; font-size: 9pt; margin: 0 auto; width: 100%; }
      th, td { padding: 2px 5px; border: 1px solid #000; }
      .r { text-align: right; }
      .d7 { background: #fee; }
      th { background: #eee; }
      .qty { border-right: none; padding-right: 0; }
      .uom { border-left: none; }
    </style>
  </head>
<body>
  <div class="page">
    <div class="content">
      <h1>CV. SHIFT KARYA TEKNOLOGI</h1>
      <h2>LAPORAN PENJUALAN HARIAN</h2>
      <h3><?= strtoupper(strftime('%A, %d %B %Y', strtotime($date))) ?></h3>
      <?php foreach ($shifts as $k => $shift): ?>
        <h5>Shift <?= $k ?></h5>
        <table>
          <thead>
            <tr>
              <th>Produk</th>
              <th colspan="2">Kwantitas</th>
              <th style="width:70px">Omset (Rp.)</th>
              <th style="width:70px">Laba (Rp.)</th>
            </tr>
          </thead>
          <tbody>
            <?php $grandTotal = 0; $grandTotalProfit = 0; ?>
            <?php foreach ($shift->itemByTypes as $type => $items): ?>
              <tr>
                <td colspan="5"><b><?= Product::typeName($type) ?></b></td>
              </tr>
              <?php $subtotal = 0; $subtotalProfit = 0; ?>
              <?php foreach ($items as $item): ?>
                <?php $subtotal += $item['price']; $subtotalProfit += $item['profit'] ?>
                <tr>
                  <td><?= e($item['name']) ?></td>
                  <td class="r qty" style="width:25px"><?= format_number($item['quantity']) ?></td>
                  <td class="uom" style="width:40px"><?= e($item['uom']) ?></td>
                  <td class="r"><?= format_number($item['price']) ?></td>
                  <td class="r"><?= format_number($item['profit']) ?></td>
                </tr>
              <?php endforeach ?>
                <?php $grandTotal += $subtotal; $grandTotalProfit += $subtotalProfit; ?>
              <tr>
                <td colspan="3" class="r"><b>Subtotal (Rp.)</b></td>
                <td class="r"><b><?= format_number($subtotal) ?></b></td>
                <td class="r"><b><?= format_number($subtotalProfit) ?></b></td>
              </tr>
              <tr>
                <td colspan="5"></td>
              </tr>
            <?php endforeach ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="3" class="r">Grand Total (Rp.)</th>
              <th class="r"><?= format_number($grandTotal) ?></th>
              <th class="r"><?= format_number($grandTotalProfit) ?></th>
            </tr>
          </tfoot>
        </table>
      <?php endforeach ?>
      </div>
    </div>
</body>