<?php
/**
 * Created by PhpStorm.
 * User: Haagsma
 * Date: 03/12/2018
 * Time: 10:48
 */
header('Access-Control-Allow-Origin: *');
include_once ("../conexao.php");
require "../../vendor/autoload.php";

if(isset($_POST['id'])){
    $cliente = $_POST['clt'];
    $itemProd = $_POST['id'];
    $montante = $_POST['val'];
    $descricao = $_POST['desc'];
}else{
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $cliente = $obj->clt;
    $itemProd = $obj->id;
    $montante = $obj->val;
    $descricao = $obj->desc;
}


$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        'ClientID',     // ClientID
        'ClientSecret'      // ClientSecret
    )
);
$payer = new \PayPal\Api\Payer();
$payer->setPaymentMethod('paypal');

$amount = new \PayPal\Api\Amount();
$amount->setTotal($montante);
$amount->setCurrency('BRL');

$transaction = new \PayPal\Api\Transaction();
$transaction->setAmount($amount);

$redirectUrls = new \PayPal\Api\RedirectUrls();
$redirectUrls->setReturnUrl("http://meusite/paypal/getApprovalPayment.php")
    ->setCancelUrl("http://meusite/pagamento");

$payment = new \PayPal\Api\Payment();
$payment->setIntent('sale')
    ->setPayer($payer)
    ->setTransactions(array($transaction))
    ->setRedirectUrls($redirectUrls);
// After Step 3
try {
    $payment->create($apiContext);
    //echo $payment->getId();
    //echo "<script> window.location.href ='".$payment->getApprovalLink()."'; </script>";
    $payId= $payment->getId();
    $conn->query("insert into tbl_historico_compras (cliente, item, `pay-id`, api) values ('$cliente', '$itemProd', '$payId', 'paypal')");
    if($conn->insert_id){
        if(isset($_POST['id'])){
            echo $payment->getApprovalLink();
        }else{
            echo json_encode($payment->getApprovalLink());
        }
    }else{
        if(isset($_POST['id'])){
            echo 'error2';
        }else{
            echo json_encode('error2');
        }
    }

}
catch (\PayPal\Exception\PayPalConnectionException $ex) {
    // This will print the detailed information on the exception.
    //REALLY HELPFUL FOR DEBUGGING
    echo 'error';
}