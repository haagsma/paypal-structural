<?php

require "../../vendor/autoload.php";
include_once ("../conexao.php");

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Transaction;


$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        'ClientID',     // ClientID
        'ClientSecret'      // ClientSecret
    )
);
$paymentId = $_GET['paymentId'];
$payment = Payment::get($paymentId, $apiContext);
// ### Payment Execute
// PaymentExecution object includes information necessary
// to execute a PayPal account payment.
// The payer_id is added to the request query parameters
// when the user is redirected from paypal back to your site
$execution = new PaymentExecution();
$execution->setPayerId($_GET['PayerID']);
// ### Optional Changes to Amount
// If you wish to update the amount that you wish to charge the customer,
// based on the shipping address or any other reason, you could
// do that by passing the transaction object with just `amount` field in it.
// Here is the example on how we changed the shipping to $1 more than before.
//$transaction = new Transaction();
//$amount = new Amount();
//$details = new Details();
//$details->setShipping(2.2)
//    ->setTax(1.3)
//    ->setSubtotal(17.50);
//$amount->setCurrency('BRL');
//$amount->setTotal(21);
//$amount->setDetails($details);
//$transaction->setAmount($amount);
//// Add the above transaction object inside our Execution object.
//$execution->addTransaction();
try {
    // Execute the payment
    // (See bootstrap.php for more on `ApiContext`)
    $result = $payment->execute($execution, $apiContext);
    // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
    //ResultPrinter::printResult("Executed Payment", "Payment", $payment->getId(), $execution, $result);
    try {

        $payment = Payment::get($paymentId, $apiContext);
        $valQuery = $conn->query("select u.saldo, c.cliente from tbl_cadastro_usuario as u left join tbl_historico_compras as c on u.id = c.cliente where c.`pay-id` = '$paymentId'")->fetch_assoc();
        $usuario = $valQuery['cliente'];
        $saldo = str_replace(',','.', $valQuery['saldo']);
        $totalCredito = str_replace('.',',', ($payment->transactions[0]->amount->total+$saldo));
        $conn->query("update tbl_cadastro_usuario set saldo = '$totalCredito' where id = $usuario");
        $valorCompra = str_replace('.', ',', $payment->transactions[0]->amount->total);
        $conn->query("insert into tbl_pagamentos (valor, cliente, tipo) values ('$valorCompra', '$usuario', 'PayPal')");
        echo "<script> window.close(); </script>";
    } catch (Exception $ex) {
        // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
        //ResultPrinter::printError("Get Payment", "Payment", null, null, $ex);
        exit(1);
    }
} catch (Exception $ex) {
    // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
    //ResultPrinter::printError("Executed Payment", "Payment", null, null, $ex);
    exit(1);
}
// NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
//ResultPrinter::printResult("Get Payment", "Payment", $payment->getId(), null, $payment);
return $payment;