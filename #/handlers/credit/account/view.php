<?php

ensure_current_user_can('view-credit-account');

require CORELIB_PATH . '/CreditAccount.php';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$item = null;
if ($id) {
  $item = $db->query("select a.*, r.name referralName, u1.username creationUserName, u2.username lastModUsername"
    . " from credit_accounts a"
    . " inner join credit_referrals r on r.id = a.referralId"
    . " inner join users u1 on u1.id = a.creationUserId"
    . " inner join users u2 on u2.id = a.lastModUserId"
    . " where a.id=$id")->fetchObject(CreditAccount::class);
}

if (!$item) {
  $_SESSION['FLASH_MESSAGE'] = "Akun tidak ditemukan.";
  exit(header('Location: ./'));
}

$item->transactions = $db->query("select *"
  . " from credit_transactions"
  . " where accountId=$item->id"
  . " order by dateTime asc")
  ->fetchAll(PDO::FETCH_OBJ);


render('credit/account/view', [
  'item' => $item,
]);