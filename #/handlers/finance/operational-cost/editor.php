<?php

require CORELIB_PATH . '/FinanceTransaction.php';
require CORELIB_PATH . '/FinanceAccount.php';

class OperationalCost
{
  public $id;
  public $categoryId;
  public $categoryName;
  public $dateTime;
  public $description;
  public $amount;
  public $ref;
  
  public $transactionId;
  public $accountId;
  
  public $creationDateTime;
  public $creationUserId;
  public $creationUsername;
  public $lastModDateTime;
  public $lastModUserId;
  public $lastModUsername;
}

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id) {
  $item = $db->query('select o.*, c.name categoryName, u1.username creationUsername, u2.username lastModUsername'
    . ' from operational_costs o'
    . ' inner join operational_cost_categories c on c.id = o.categoryId'
    . ' inner join users u1 on u1.id=o.creationUserId'
    . ' inner join users u2 on u2.id=o.lastModUserId'
    . ' where o.id=' . $id)->fetchObject(OperationalCost::class);
  if (!$item) {
    $_SESSION['FLASH_MESSAGE'] = 'Biaya operasional tidak ditemukan';
    header('Location: ./');
    exit;
  }
  $financeTransaction = FinanceTransaction::findByReference('operational-cost', $item->id);
  if ($financeTransaction) {
    $item->accountId = (int)$financeTransaction->accountId;
    $item->transactionId = (int)$financeTransaction->id;
  }
}
else {
  $item = new OperationalCost();
  $item->dateTime = date('Y-m-d H:i:s');
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : 'save';
  if ($action === 'delete') {
    $db->beginTransaction();
    try {
      $db->query('delete from operational_costs where id=' . $item->id);
      if ($item->transactionId) {
        FinanceTransaction::delete($item->transactionId);
        FinanceAccount::updateBalance($item->accountId);
      }
    }
    catch (Exception $ex) {
      $_SESSION['FLASH_MESSAGE'] = 'Biaya tidak dapat dihapus.';
      header('Location: ?id=' . $category->id);
      exit;  
    }
    $db->commit();
    
    $_SESSION['FLASH_MESSAGE'] = 'Biaya ' . e($category->name) . ' telah dihapus.';
    header('Location: ./');
    exit;
  }
  
  $item->categoryId = isset($_POST['categoryId']) ? (int)$_POST['categoryId'] : 0;
  $item->description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
  $item->amount = from_locale_number(isset($_POST['amount']) ? trim((string)$_POST['amount']) : '0');
  $item->ref = isset($_POST['ref']) ? trim((string)$_POST['ref']) : '';
  $item->dateTime = to_mysql_datetime(isset($_POST['dateTime']) ? trim((string)$_POST['dateTime']) : '');
  $item->accountId = isset($_POST['accountId']) ? (int)$_POST['accountId'] : 0;
  
  if (empty($item->description))
    $errors['description'] = 'Deskripsi harus diisi.';
  
  if (!$item->dateTime)
    $errors['dateTime'] = 'Format tanggal dan waktu tidak valid.';
  
  if ($item->amount === 0)
    $errors['amount'] = 'Nominal tidak valid.';
    
  if (empty($errors)) {
    $now = date('Y-m-d H:i:s');
    
    $db->beginTransaction();
    if ($item->id == 0) {
      $q = $db->prepare('insert into operational_costs'
        . ' ( categoryId, dateTime, description, amount, ref, creationUserId, creationDateTime, lastModUserId, lastModDateTime)'
        . ' values'
        . ' (:categoryId,:dateTime,:description,:amount,:ref,:creationUserId,:creationDateTime,:lastModUserId,:lastModDateTime)'
      );
      $q->bindValue(':creationUserId', $_SESSION['CURRENT_USER']->id);
      $q->bindValue(':creationDateTime', $now);
      $q->bindValue(':lastModUserId', $_SESSION['CURRENT_USER']->id);
      $q->bindValue(':lastModDateTime', $now);
    }
    else {
      $q = $db->prepare('update operational_costs set'
        . ' categoryId=:categoryId,'
        . ' dateTime=:dateTime,'
        . ' description=:description,'
        . ' amount=:amount,'
        . ' ref=:ref'
        . ' where id=:id'
      );
      $q->bindValue(':id', $item->id);
    }
    
    $q->bindValue(':categoryId', $item->categoryId);
    $q->bindValue(':dateTime', $item->dateTime);
    $q->bindValue(':description', $item->description);
    $q->bindValue(':amount', $item->amount);
    $q->bindValue(':ref', $item->ref);
    $q->execute();
    
    $transaction = new FinanceTransaction();
    $transaction->id = $item->transactionId;
    $transaction->accountId = $item->accountId;
    $transaction->type = FinanceTransaction::Expense;
    $transaction->amount = -$item->amount;
    $transaction->dateTime = $item->dateTime;
    $transaction->description = $item->description;
    $transaction->refType = 'operational-cost';
    $transaction->userId = $_SESSION['CURRENT_USER']->id;
    
    if (!$item->id) {
      $transaction->refId = $db->lastInsertId();
    }
    else if ($q->rowCount() > 0) {
      $q = $db->prepare('update operational_costs set lastModUserId=:lastModUserId, lastModDateTime=:lastModDateTime where id=:id');
      $q->bindValue(':lastModUserId', $_SESSION['CURRENT_USER']->id);
      $q->bindValue(':lastModDateTime', $now);
      $q->bindValue(':id', $item->id);
      $q->execute();
      
      $transaction->refId = $item->id;
    }
    
    FinanceTransaction::save($transaction);
    FinanceAccount::updateBalance($item->accountId);
    
    $db->commit();

    $_SESSION['FLASH_MESSAGE'] = 'Biaya ' . format_operational_cost_code($item->id). ' telah disimpan.';
    header('Location: ./');
    exit;
  }
}

$sql = 'select * from operational_cost_categories where active=1';
if ($item->categoryId)
  $sql .= ' or id='.$item->categoryId;
$sql.= ' order by name asc';

$categories = $db->query($sql)->fetchAll(PDO::FETCH_OBJ);

$accounts = get_current_user_finance_accounts();

render('finance/operational-cost/editor', [
  'item' => $item,
  'categories' => $categories,
  'accounts' => $accounts,
  'errors' => $errors,
]);
