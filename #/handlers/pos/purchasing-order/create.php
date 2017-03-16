<?php

ensure_current_user_can('add-sales-order');

$q = $db->prepare('insert into purchasing_orders'
  . ' ( openDateTime, lastModDateTime)'
  . ' values '
  . ' (:openDateTime,:openDateTime)');

$q->bindValue(':openDateTime', date('Y-m-d H:i:s'));
$q->execute();

$id = $db->lastInsertId();

header('Location: ./editor?id=' . $id);
exit;