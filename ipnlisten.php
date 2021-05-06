<?php
require(__DIR__.'/form.php');

$paypalUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

while(1) {
        // Handle the PayPal response.

        // Create a connection to the database.
        //$db = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);

        // Assign posted variables to local data array.

        $data = [
            'payment_status' => $_POST['payment_status'],
            'payment_amount' => $_POST['mc_gross'],
            'payment_currency' => $_POST['mc_currency'],
            'txn_id' => $_POST['txn_id'],
            'receiver_email' => $_POST['receiver_email'],
            'payer_email'=>$_POST['payer_email'],
            'custom' => $_POST['custom'],
        ];

        //check payment
        error_log('payment:'.$data['payment_status']);

        if (empty($data['payment_status'])||$data['payment_status']!='Completed')
        {
            error_log("payment is not completed");
            break;
        }
        $invoice=$_POST['invoice'];
        $db = ierg4210_DB();
        $sql = $db->prepare("SELECT digest, salt FROM orders where oid=?;");
        $sql->bindParam(1,$invoice);
        $sql->execute();
        $res=$sql->fetch(PDO::FETCH_ASSOC);
        $digestOld=$res['digest'];
        $salt=$res['salt'];

        error_log('get digest '.$digestOld);
        error_log('get salt '.$salt);

        $currency="HKD";
        if ($currency!=$data['payment_currency']){
            error_log("incorect curr");
            break;
        }

        $email="sb-upw47p6016749@business.example.com";
        if($data['receiver_email']==$email){
			error_log("correct email");
		}else{
			error_log("incorect email");
			break;
		}

        $i=1;
        $list=array();
        $pidList=array();
        $nameList=array();
        $qtyList=array();
        $totalPriceList=array();
        $priceList=array();
        $shoppingcart_info= "";
        $totalPrice=0;
        do{
            error_log('the i='.$i);
            $pidList[$i]=$_POST['item_number'.$i];
            $nameList[$i]=$_POST['item_name'.$i];
            $qtyList[$i]=$_POST['quantity'.$i];
            $totalPriceList[$i]= (int)($_POST['mc_gross_'.$i]);
            $tempamount=(int) $qtyList[$i];
            $temp=(int)($totalPriceList[$i]/$tempamount);
            $priceList[$i]==$temp;
            $shoppingcart_info.=$pidList[$i];
            $shoppingcart_info.='&';
            $shoppingcart_info.=$nameList[$i];
            $shoppingcart_info.='&';
            $shoppingcart_info.=$qtyList[$i];
            $shoppingcart_info.='&';
            $shoppingcart_info.=$priceList[$i];
            $shoppingcart_info.='|';
            $totalPrice+=$totalPriceList[$i];
        }while ($_POST['item_number'.++$i]);
        error_log('shopping_cart '.$shoppingcart_info);
        error_log($data["payment_currency"].':'.$data["receiver_email"].':'.$salt.':'.$shoppingcart_info.':'.$totalPrice);
        $digest=sha1($data["payment_currency"].':'.$data["receiver_email"].':'.$salt.':'.$shoppingcart_info.':'.$totalPrice);
        error_log('newdigest '.$digest);
        error_log('olddigest '.$digestOld);

        // We need to verify the transaction comes from PayPal and check we've not
        // already processed the transaction before adding the payment to our
        // database.
        if ( checkTxnid($data['txn_id'])&&verifyTransaction($_POST) ) {
            error_log("both_true");

            if (addPayment($data) !== false) {
                    // Payment successfully added into db.
			break;
                }
            }else{
                //Payment failed
		break;
            }
}

?>
