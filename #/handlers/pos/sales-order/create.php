<?php

$q = $db->prepare('insert into sales_orders'
  . ' ( openDateTime, lastModDateTime)'
  . ' values '
  . ' (:openDateTime,:openDateTime)');

$q->bindValue(':openDateTime', date('Y-m-d H:i:s'));
$q->execute();

$id = $db->lastInsertId();

header('Location: ./editor?id=' . $id);
exit;