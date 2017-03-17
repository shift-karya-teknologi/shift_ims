<?php

ensure_current_user_can('view-reports');

?>
<html>
  <head>
    <?= render('head') ?>
  </head>
<body>
  <div class="mdl-grid">
    <div class="mdl-cell mdl-cell--12-col">
    <h5>Daftar Laporan</h5>
    <ul>
      <li><a href="./daily-sales-by-products">Laporan Penjualan Harian</a></li>
      <li><a href="./monthly-sales">Laporan Penjualan Bulanan</a></li>
    </ul>
   </div>
  </div>
</body>