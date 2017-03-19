<?php

ensure_current_user_can('transfer-cash');
  
require CORELIB_PATH . '/FinanceAccount.php';
require CORELIB_PATH . '/FinanceTransaction.php';

$data = new stdClass();
$data->fromId = isset($_REQUEST['fromId']) ? (int)$_REQUEST['fromId'] : 0;
$data->toId = 0;
$data->amount = 0;
$data->description = '';

$errors = [];

$data->fromAccountName = $db->query('select name from finance_accounts where id=' . $data->fromId)->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data->amount = isset($_POST['amount']) ? (int) trim(str_replace('.', '', $_POST['amount'])) : 0;
  $data->toId = isset($_POST['toId']) ? (int)$_POST['toId'] : 0;
  $data->description = isset($_POST['description']) ? trim($_POST['description']) : '';
  
  if (empty($data->amount))
    $errors['amount'] = 'Jumlah transfer harus diisi.';
  
  if (empty($data->description))
    $errors['description'] = 'Deskripsi harus diisi.';
    
  if (empty($errors)) {
    $destinationAccountName = $db->query('select name from finance_accounts where id=' . $data->toId)->fetchColumn();
    $dateTime = date('Y-m-d H:i:s');
    
    $trxFrom = new FinanceTransaction();
    $trxFrom->accountId = $data->fromId;
    $trxFrom->type = FinanceTransaction::TYPE_TRANSFER;
    $trxFrom->amount = -$data->amount;
    $trxFrom->dateTime = $dateTime;
    $trxFrom->description = "Transfer ke " . $destinationAccountName . ': ' . $data->description;
    $trxFrom->refType = '';
    $trxFrom->refId = '';
    $trxFrom->externalRef = '';
    $trxFrom->creationDateTime = $dateTime;
    $trxFrom->creationUserId = $_SESSION['CURRENT_USER']->id;
    $trxFrom->lastModDateTime = $dateTime;
    $trxFrom->lastModUserId = $_SESSION['CURRENT_USER']->id;
    
    $trxTo = clone $trxFrom;
    $trxTo->accountId = $data->toId;
    $trxTo->amount = $data->amount;
    $trxTo->description = "Transfer dari " . e($data->fromAccountName) . ': ' . $data->description;
    
    $db->beginTransaction();
    
    FinanceTransaction::save($trxFrom);
    FinanceTransaction::save($trxTo);
    
    FinanceAccount::updateBalance($data->fromId);
    FinanceAccount::updateBalance($data->toId);

    $db->commit();
      
    $_SESSION['FLASH_MESSAGE'] = 'Transfer kas disimpan.';
    header('Location: ./');
    exit;
  }
}

$accounts = $db->query('select * from finance_accounts where id<>' . $data->fromId)->fetchAll(PDO::FETCH_OBJ);

render('finance/account/transfer', [
  'data' => $data,
  'accounts' => $accounts,
  'errors' => $errors,
]);
