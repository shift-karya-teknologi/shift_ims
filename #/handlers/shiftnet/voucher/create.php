<?php

require CORELIB_PATH . '/FinanceAccount.php';
require CORELIB_PATH . '/FinanceTransaction.php';

class Voucher
{
  public $id;
  public $code;
  public $duration;
  public $price;
  public $expirationDateTime;
  public $creationDateTime;
  public $operatorId;
}

abstract class VoucherGenerator
{
  private static $_dayCodes   = "6Y2ENJ3T9Z8QBUDGX5HWKCMPA7FVL4R";
  private static $_monthCodes = ["HBJGMDAFKCEL", "RQNSPVYXTZUW", "I183O4927650"];
  private static $_base36Map  = ["0123456789abcdefghijklmnopqrstuvwxyz", "D7ZO61X04VBYLF9A3RC5HIMTP8WUNKSGQJ2E"];

  public static function generateVoucher($money)
  {
    $voucher = new Voucher();
    $voucher->price = $money;
    $voucher->creationDateTime = new DateTime('now');
    $voucher->code = self::_generateVoucherCode($voucher->creationDateTime);
    $voucher->duration = self::_convertMoneyToDuration($money);
    $voucher->expirationDateTime = self::_generateExpirationDateTime($voucher);
    $voucher->operatorId = $_SESSION['CURRENT_USER']->id;
    return $voucher;
  }

  private static function _generateExpirationDateTime(Voucher $voucher)
  {
    $dateTime = clone $voucher->creationDateTime;
    $dateTime->add(new DateInterval('PT' . floor(($voucher->duration / 60) * 12) . 'H'));
    return $dateTime;
  }
  
  private static function _generateVoucherCode(\DateTime $dateTime)
  {
    sleep(1);
    
    $day   = (int)$dateTime->format('d');
    $month = (int)$dateTime->format('m');
    
    $code = "";
    $code .= self::$_dayCodes[$day - 1];
    $code .= self::$_monthCodes[rand(0, 2)][$month - 1];

    $plain = base_convert((string)time(), 10, 36);
    $plain = str_pad($plain, 6, '0', STR_PAD_LEFT);
    $plain = substr($plain, strlen($plain)-4, 4);

    for ($i = 0; $i < 4; $i++)
      $code .= self::$_base36Map[1][strpos(self::$_base36Map[0], $plain[$i])];

    return $code;
  }

  private static function _convertMoneyToDuration($money)
  {
    return floor(($money <= 3000) ? ($money / 50) : ($money / 41.66));
  }

}


$errors = [];

$amount = 0;

$amounts = [
  1000  => 'Rp. 1.000',
  1500  => 'Rp. 1.500',
  2000  => 'Rp. 2.000',
  3000  => 'Rp. 3.000',
  5000  => 'Rp. 5.000',
  7500  => 'Rp. 7.500',
  10000 => 'Rp. 10.000',
  12500 => 'Rp. 12.500',
  15000 => 'Rp. 15.000',
  17500 => 'Rp. 17.500',
  20000 => 'Rp. 20.000',
];

$productsIdbyPrices = [
  1000  => 1,
  1500  => 2,
  2000  => 3,
  3000  => 4,
  5000  => 5,
  7500  => 6,
  10000 => 7,
  12500 => 8,
  15000 => 9,
  17500 => 10,
  20000 => 11,
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $amount   = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
  $password = isset($_POST['operatorPassword']) ? (string)$_POST['operatorPassword'] : 0;
  
 if (!array_key_exists($amount, $amounts)) {
    $errors['amount'] = 'Tentukan nominal voucher';
 }
  
  if (empty($password)) {
    $errors['operatorPassword'] = 'Masukkan kata sandi!';
  }
  else if (encrypt_password($password) !== get_current_user_password() ) {
    $errors['operatorPassword'] = 'Kata sandi anda salah!';
  }
  
  if (empty($errors)) {
    $voucher = VoucherGenerator::generateVoucher($amount);
    $dateTime = $voucher->creationDateTime->format('Y-m-d H:i:s');
    
    $db->beginTransaction();
    
    $q = $db->prepare('insert into shiftnet_voucher_transactions'
                     . ' ( code, price, duration, operatorId, expirationDateTime, creationDateTime)'
                     . 'values'
                     . ' (:code,:price,:duration,:operatorId,:expirationDateTime,:creationDateTime)');
    $q->bindValue(':operatorId', $voucher->operatorId);
    $q->bindValue(':code', $voucher->code);
    $q->bindValue(':duration', $voucher->duration);
    $q->bindValue(':price', $voucher->price);
    $q->bindValue(':creationDateTime', $dateTime);
    $q->bindValue(':expirationDateTime', $voucher->expirationDateTime->format('Y-m-d H:i:s'));
    $q->execute();
    
    $voucher->id = $db->lastInsertId();
    
    $q = $db->prepare('insert into sales_orders'
      . ' (lastModDateTime,openDateTime,closeDateTime,status,totalCost,totalPrice,openUserId,lastModUserId,closeUserId)'
      . 'values'
      . ' (:dateTime,:dateTime,:dateTime,1,0,:total,:userId,:userId,:userId)');
    $q->bindValue(':dateTime', $dateTime);
    $q->bindValue(':userId', $_SESSION['CURRENT_USER']->id);
    $q->bindValue(':total', $voucher->price);
    $q->execute();
    
    $salesOrderId = $db->lastInsertId();
    $q = $db->prepare('insert into sales_order_details'
      . ' (parentId, productId, quantity, cost, price, subtotalCost, subtotalPrice)'
      . 'values'
      . ' (:parentId,:productId,       1,     0,:price,           0, :price)');
    $q->bindValue(':parentId', $salesOrderId);
    $q->bindValue(':productId', $productsIdbyPrices[$voucher->price]);
    $q->bindValue(':price', $voucher->price);
    $q->execute();
    
    $transaction = new FinanceTransaction();
    $transaction->accountId = $cfg['store_account_id'];
    $transaction->type = FinanceTransaction::TYPE_INCOME;
    $transaction->amount = $voucher->price;
    $transaction->dateTime = $dateTime;
    $transaction->description = "Penjualan Voucher ShiftNet " . format_number($voucher->price);
    $transaction->refType = 'sales-order';
    $transaction->refId = $salesOrderId;
    $transaction->userId = $_SESSION['CURRENT_USER']->id;
    
    FinanceTransaction::save($transaction);
    FinanceAccount::updateBalance($transaction->accountId);
    
    $q = $db->prepare('insert into shiftnet_active_vouchers'
                      . ' ( voucherId, code, remainingDuration, activeClientId, lastActiveUsername)'
                      . 'values'
                      . ' (:voucherId,:code,:remainingDuration, null, null)');
    $q->bindValue(':voucherId', $voucher->id);
    $q->bindValue(':code', $voucher->code);
    $q->bindValue(':remainingDuration', $voucher->duration);
    $q->execute();
    
    // TODO: log aktivitas operator
    
    $db->commit();
    
    header('Location: ./view?id=' . $voucher->id);
    exit;
  }
}

render('layout', [
  'title' => 'Buat Voucher',
  'sidenav' => render('shiftnet/sidenav', true),
  'headnav' => '
    <a href="./" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">close</i>
    </a>',
  'content' => render('shiftnet/voucher/create', [
    'amounts'  => $amounts,
    'amount'   => $amount,
    'errors'   => $errors,
  ], true)
]);