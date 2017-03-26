<?php

$items = [];
$q = $db->query('select * from finance_transactions');
while ($item = $q->fetchObject()) {
  $items[] = $item;
}


?>
<!DOCTYPE html>
<html>
  <head>
    <?= render('head') ?>
  </head>
  <body>
    <?php foreach ($items as $item): ?>
      
    <?php endforeach ?>
  </body>
</html>