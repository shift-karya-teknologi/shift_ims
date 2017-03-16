<?php

ensure_current_user_can('add-sales-order');

$q = $db->prepare('insert into sales_orders'
  . ' (openDateTime,lastModDateTime,openUserId,lastModUserId)'
  . ' values '
  . ' (:dateTime,:dateTime,:userId,:userId)');

$q->bindValue(':dateTime', date('Y-m-d H:i:s'));
$q->bindValue(':userId', $_SESSION['CURRENT_USER']->id);
$q->execute();

$id = $db->lastInsertId();

header('Location: ./editor?id=' . $id);
exit;