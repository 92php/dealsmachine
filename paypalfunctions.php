<?php
	require_once('lib/front_global.php');
	$cur_lang = empty($_GET['lang'])?'':$_GET['lang'];
	if($cur_lang!='en'&&!empty($lang_arr)&&in_array($cur_lang,$lang_arr)){
		$cur_lang_url = $cur_lang.'/';
	}else {
		$cur_lang_url='';
	}
	$PROXY_HOST = '127.0.0.1';
	$PROXY_PORT = '808';
	$SandboxFlag = true;
	$sBNCode = "PP-ECWizard";
	$HDRIMG = "https://cloud6.faout.com/imagecache/A/ximages/ppplogo.jpg";
	$USE_PROXY = false;
	$version="94.0";
    if (IS_LOCAL) {
        $SandboxFlag = true;
    }
    else {
        $SandboxFlag = false;
    }	
	if ($SandboxFlag == true)
	{
		$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
		$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
        if(!empty($pay_step) && $pay_step=='checkout')//checkout页面付款账号判断
        {
            if ($_SESSION['Payment_Amount'] <= 9) {//9美金以下
                $API_UserName="dyj2009_api1.inesun.com";
                $API_Password="K3XKMWSD9TW4VUKJ";
                $API_Signature="AhSULXLxrQ-3WOfjwirTZ6E.hl-BARV04IvxOnPzmfZXWz7hhVbMsFe-";
            }
            elseif ($_SESSION['Payment_Amount'] >=100) {//100-150美金
                $API_UserName="dyj2009_api1.inesun.com";
                $API_Password="K3XKMWSD9TW4VUKJ";
                $API_Signature="AhSULXLxrQ-3WOfjwirTZ6E.hl-BARV04IvxOnPzmfZXWz7hhVbMsFe-";
            }
            else {//其它金额
                $API_UserName="dyj2009_api1.inesun.com";
                $API_Password="K3XKMWSD9TW4VUKJ";
                $API_Signature="AhSULXLxrQ-3WOfjwirTZ6E.hl-BARV04IvxOnPzmfZXWz7hhVbMsFe-";
            }
        }
        else //cart页面
        {
            $API_UserName="dyj2009_api1.inesun.com";
            $API_Password="K3XKMWSD9TW4VUKJ";
            $API_Signature="AhSULXLxrQ-3WOfjwirTZ6E.hl-BARV04IvxOnPzmfZXWz7hhVbMsFe-";
        }				
		//$returnURL = DOMAIN_CART."/".$cur_lang_url."m-flow-a-exp_checkout.htm";
		//$cancelURL = DOMAIN_CART."/".$cur_lang_url."m-flow-a-fails.htm";
	}
	else
	{
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		$PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";		
        if($pay_step=='checkout')//checkout页面付款账号判断
        {
            if ($_SESSION['Payment_Amount'] <= 9) {//9美金以下
                $API_UserName="davismicropayment_api1.gmail.com";
                $API_Password="GX6A6T4PDD4DZYHG";
                $API_Signature="AtDirUzWJyT5rlVgagdrMiMSFFYUAHeqSQhdgA4i8R-O7ASgqciQ7I-E";
            }
            elseif ($_SESSION['Payment_Amount'] >=100) {//100-150美金
                $API_UserName="davismicropayment_api1.gmail.com";
                $API_Password="GX6A6T4PDD4DZYHG";
                $API_Signature="AtDirUzWJyT5rlVgagdrMiMSFFYUAHeqSQhdgA4i8R-O7ASgqciQ7I-E";
            }
            else {//其它金额
                $API_UserName="davismicropayment_api1.gmail.com";
                $API_Password="GX6A6T4PDD4DZYHG";
                $API_Signature="AtDirUzWJyT5rlVgagdrMiMSFFYUAHeqSQhdgA4i8R-O7ASgqciQ7I-E";
            }
        }
        else //cart页面
        {
			$API_UserName="davismicropayment_api1.gmail.com";
			$API_Password="GX6A6T4PDD4DZYHG";
			$API_Signature="AtDirUzWJyT5rlVgagdrMiMSFFYUAHeqSQhdgA4i8R-O7ASgqciQ7I-E";
        }
		//$returnURL = DOMAIN_CART.'/'.$cur_lang_url."m-flow-a-exp_checkout.htm";
		//$cancelURL = DOMAIN_CART.'/'.$cur_lang_url."m-flow-a-cart.htm";
	}

	if (session_id() == "") {
		session_start();
	}

	function CallShortcutExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL,$cart_goods)
	{
		global $HDRIMG;

		$amt = 0;
		foreach ($cart_goods['goods_list'] as $k=>$v){
			$amt += $v['goods_price']*$v['goods_number'];
		}
		$amt =
		$discount = $amt - $cart_goods['total']['goods_price'];
		if($discount>0){
			$cart_goods['goods_list'][]=array('goods_title'=>'Discount','goods_price'=>-"$discount",'goods_number'=>'1');
		}

		$amt = 0;
		foreach ($cart_goods['goods_list'] as $k=>$v){
			$amt += $v['goods_price']*$v['goods_number'];
		}


		$nvpstr="&AMT=". $amt;
		$kk =0;
		foreach ($cart_goods['goods_list'] as $k=>$v){
	        $nvpstr = $nvpstr . "&L_NAME$k=".urlencode($v['goods_title']);
	       // $amt += $v['goods_price']*$v['goods_number'];
			$nvpstr = $nvpstr . "&L_AMT$k=".$v['goods_price'];
			$nvpstr = $nvpstr . "&L_QTY$k=$v[goods_number]";
			$kk = $k;
		}
        $nvpstr = $nvpstr . "&ITEMAMT=".$amt;
		$nvpstr = $nvpstr . "&PAYMENTACTION=" . $paymentType;
		$nvpstr = $nvpstr . "&ReturnUrl=" . $returnURL;
		$nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
		$nvpstr = $nvpstr . "&HDRIMG=" . urlencode($HDRIMG);
		$nvpstr = $nvpstr . "&CURRENCYCODE=" . $currencyCodeType;
        if(!empty($_SESSION['access_token'])) {
        	$nvpstr = $nvpstr . "&IDENTITYACCESSTOKEN=" . $_SESSION['access_token'];
        }
		$_SESSION["currencyCodeType"] = $currencyCodeType;
		$_SESSION["PaymentType"] = $paymentType;

		//'---------------------------------------------------------------------------------------------------------------
		//' Make the API call to PayPal
		//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
		//' If an error occured, show the resulting errors
		//'---------------------------------------------------------------------------------------------------------------
	    $resArray=hash_call("SetExpressCheckout", $nvpstr);
	    if(empty($resArray["ACK"])){
	    	header('Location:/'.$cur_lang_url.'m-flow-a-cart.htm');
	    	exit();
	    }
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS")
		{
			$token = urldecode($resArray["TOKEN"]);
			$_SESSION['TOKEN']=$token;
		}

	    return $resArray;
	}

    //checkout页面paypal
    function CallShortcutExpressCheckout_1( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $cart_goods, $order)
	{
		//------------------------------------------------------------------------------------------------------------------------------------
		// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
		$HDRIMG = "https://cloud6.faout.com/imagecache/A/ximages/ppplogo.jpg";
	    $amt = 0;
		foreach ($cart_goods['goods_list'] as $k=>$v){
			$amt += $v['goods_price']*$v['goods_number'];
		}

        //折扣
        if($order['goods_amount']>0)
        {
		    $discount = price_format($amt - $order['goods_amount'], false);
        }
		if($discount>0){
			$cart_goods['goods_list'][]=array('goods_name'=>'Discount','goods_price'=>-"$discount",'goods_number'=>'1');
		}

        //积分
        $point_money=$order['point_money'];
        if ($point_money > 0) {
            $cart_goods['goods_list'][]=array('goods_name'=>'DM Points','goods_price'=>-"$point_money",'goods_number'=>'1');
        }

        //产品
		$amt = 0;
		$kk =0;
		$nvpstr = '';
		foreach ($cart_goods['goods_list'] as $k=>$v){
	        $nvpstr = $nvpstr . "&L_NAME$k=".urlencode($v['goods_name']);
			$nvpstr = $nvpstr . "&L_AMT$k=".$v['goods_price'];
			$nvpstr = $nvpstr . "&L_QTY$k=".$v['goods_number'];
			$nvpstr .=  isset($v['goods_sn']) ? "&L_NUMBER$k={$v['goods_sn']}" : '';

            $amt += $v['goods_price']*$v['goods_number'];
			$kk = $k;
		}

        //运费
        if ($order['free_shipping_fee'] > 0 || $order['shipping_fee'] > 0 || $order['Need_Traking_number'] > 0) {
            $mun=++$kk;
            $nvpstr = $nvpstr . "&L_NAME".$mun."=Shipping fee";
            $nvpstr = $nvpstr . "&L_AMT".$mun."=".(price_format($order['shipping_fee'] + $order['free_shipping_fee'] + $order['Need_Traking_number'] , false));
            $nvpstr = $nvpstr . "&L_QTY".$mun."=1";
            $amt=$amt+(price_format($order['shipping_fee'] + $order['free_shipping_fee'] + $order['Need_Traking_number'] , false));
        }

        //保险费
        if ($order['insure_fee'] > 0) {
            $mun1=++$kk;
            $nvpstr = $nvpstr . "&L_NAME".$mun1."=Insurance";
            $nvpstr = $nvpstr . "&L_AMT".$mun1."=".$order['insure_fee'];
            $nvpstr = $nvpstr . "&L_QTY".$mun1."=1";
            $amt=$amt+$order['insure_fee'];
        }

        $nvpstr = $nvpstr . "&AMT=". $amt;
        $nvpstr = $nvpstr . "&ITEMAMT=".$amt;
		$nvpstr = $nvpstr . "&PAYMENTACTION=" . $paymentType;
		$nvpstr = $nvpstr . "&ReturnUrl=" . $returnURL;
		$nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
		$nvpstr = $nvpstr . "&CURRENCYCODE=" . $currencyCodeType;
		$nvpstr = $nvpstr . "&HDRIMG=" . urlencode($HDRIMG);

        //$nvpstr = $nvpstr . "&NOSHIPPING=1";//hidden shipping address
        /*
        //shipping address
        $nvpstr = $nvpstr . "&ADDROVERRIDE=1";
        $nvpstr = $nvpstr . "&SHIPTOSTREET=".$order['address'];
        $nvpstr = $nvpstr . "&SHIPTOCITY=".$order['city'];
        $nvpstr = $nvpstr . "&SHIPTOSTATE=".$order['province'];
        $nvpstr = $nvpstr . "&SHIPTOCOUNTRYCODE=".$order['country_code'];
        $nvpstr = $nvpstr . "&SHIPTOZIP=".$order['zipcode'];
        */

        //echo $nvpstr;exit;
		$_SESSION["currencyCodeType"] = $currencyCodeType;
		$_SESSION["PaymentType"] = $paymentType;

		//'---------------------------------------------------------------------------------------------------------------
		//' Make the API call to PayPal
		//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
		//' If an error occured, show the resulting errors
		//'---------------------------------------------------------------------------------------------------------------
	    $resArray=hash_call("SetExpressCheckout", $nvpstr);
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS")
		{
			$token = urldecode($resArray["TOKEN"]);
			$_SESSION['TOKEN']=$token;
		}

	    return $resArray;
	}

	/*
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
	' Inputs:
	'		paymentAmount:  	Total value of the shopping cart
	'		currencyCodeType: 	Currency code value the PayPal API
	'		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
	'		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
	'		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
	'		shipToName:		the Ship to name entered on the merchant's site
	'		shipToStreet:		the Ship to Street entered on the merchant's site
	'		shipToCity:			the Ship to City entered on the merchant's site
	'		shipToState:		the Ship to State entered on the merchant's site
	'		shipToCountryCode:	the Code for Ship to Country entered on the merchant's site
	'		shipToZip:			the Ship to ZipCode entered on the merchant's site
	'		shipToStreet2:		the Ship to Street2 entered on the merchant's site
	'		phoneNum:			the phoneNum  entered on the merchant's site
	'--------------------------------------------------------------------------------------------------------------------------------------------
	*/
	function CallMarkExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL,
									  $cancelURL, $shipToName, $shipToStreet, $shipToCity, $shipToState,
									  $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum
									)
	{
		//------------------------------------------------------------------------------------------------------------------------------------
		// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation

		$nvpstr="&Amt=". $paymentAmount;
		$nvpstr = $nvpstr . "&PAYMENTACTION=" . $paymentType;
		$nvpstr = $nvpstr . "&ReturnUrl=" . $returnURL;
		$nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
		$nvpstr = $nvpstr . "&CURRENCYCODE=" . $currencyCodeType;
		$nvpstr = $nvpstr . "&ADDROVERRIDE=1";
		$nvpstr = $nvpstr . "&SHIPTONAME=" . $shipToName;
		$nvpstr = $nvpstr . "&SHIPTOSTREET=" . $shipToStreet;
		$nvpstr = $nvpstr . "&SHIPTOSTREET2=" . $shipToStreet2;
		$nvpstr = $nvpstr . "&SHIPTOCITY=" . $shipToCity;
		$nvpstr = $nvpstr . "&SHIPTOSTATE=" . $shipToState;
		$nvpstr = $nvpstr . "&SHIPTOCOUNTRYCODE=" . $shipToCountryCode;
		$nvpstr = $nvpstr . "&SHIPTOZIP=" . $shipToZip;
		$nvpstr = $nvpstr . "&PHONENUM=" . $phoneNum;

		$_SESSION["currencyCodeType"] = $currencyCodeType;
		$_SESSION["PaymentType"] = $paymentType;

		//'---------------------------------------------------------------------------------------------------------------
		//' Make the API call to PayPal
		//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
		//' If an error occured, show the resulting errors
		//'---------------------------------------------------------------------------------------------------------------
	    $resArray=hash_call("SetExpressCheckout", $nvpstr);
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS")
		{
			$token = urldecode($resArray["TOKEN"]);
			$_SESSION['TOKEN']=$token;
		}

	    return $resArray;
	}

	/*
	'-------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
	'
	' Inputs:
	'		None
	' Returns:
	'		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
	'-------------------------------------------------------------------------------------------
	*/
	function GetShippingDetails( $token )
	{
		//'--------------------------------------------------------------
		//' At this point, the buyer has completed authorizing the payment
		//' at PayPal.  The function will call PayPal to obtain the details
		//' of the authorization, incuding any shipping information of the
		//' buyer.  Remember, the authorization is not a completed transaction
		//' at this state - the buyer still needs an additional step to finalize
		//' the transaction
		//'--------------------------------------------------------------

	    //'---------------------------------------------------------------------------
		//' Build a second API request to PayPal, using the token as the
		//'  ID to get the details on the payment authorization
		//'---------------------------------------------------------------------------
	    $nvpstr="&TOKEN=" . $token;

		//'---------------------------------------------------------------------------
		//' Make the API call and store the results in an array.
		//'	If the call was a success, show the authorization details, and provide
		//' 	an action to complete the payment.
		//'	If failed, show the error
		//'---------------------------------------------------------------------------
	    $resArray=hash_call("GetExpressCheckoutDetails",$nvpstr);
	    $ack = strtoupper($resArray["ACK"]);
		if($ack == "SUCCESS")
		{
			$_SESSION['payer_id'] =	$resArray['PAYERID'];
		}
		return $resArray;
	}

	/*
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
	'
	' Inputs:
	'		sBNCode:	The BN code used by PayPal to track the transactions from a given shopping cart.
	' Returns:
	'		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
	'--------------------------------------------------------------------------------------------------------------------------------------------
	*/
	function ConfirmPayment( $FinalPaymentAmt )
	{
		/* Gather the information to make the final call to
		   finalize the PayPal payment.  The variable nvpstr
		   holds the name value pairs
		   */


		//Format the other parameters that were stored in the session from the previous calls
		$token 				= urlencode($_SESSION['TOKEN']);
		$paymentType 		= urlencode($_SESSION['PaymentType']);
		$currencyCodeType 	= urlencode($_SESSION['currencyCodeType']);
		$payerID 			= urlencode($_SESSION['payer_id']);
		$INVNUM 			= urlencode($_SESSION['orderno']);

		$serverName 		= urlencode($_SERVER['SERVER_NAME']);

		$nvpstr  = '&TOKEN=' . $token . '&PAYERID=' . $payerID . '&PAYMENTACTION=' . $paymentType . '&AMT=' . $FinalPaymentAmt;
		$nvpstr .= '&CURRENCYCODE=' . $currencyCodeType . '&IPADDRESS=' . $serverName. '&INVNUM=' . $INVNUM;

           /* Make the call to PayPal to finalize payment
		    If an error occured, show the resulting errors
		    */
		$resArray=hash_call("DoExpressCheckoutPayment",$nvpstr);

		/* Display the API response back to the browser.
		   If the response from PayPal was a success, display the response parameters'
		   If the response was an error, display the errors received using APIError.php.
		   */
		$ack = strtoupper($resArray["ACK"]);
		//保存订单paypal付款相关信息
		if( $ack == "SUCCESS" )
		{
			$shippingdetails = GetShippingDetails($token);
			$order_paypal_info = array(
										'order_sn' => $INVNUM,
										'email' => addslashes($shippingdetails['EMAIL']),
										'transaction_id' => addslashes($resArray['TRANSACTIONID']),
										'payerstatus' => addslashes($shippingdetails['PAYERSTATUS']),
										'addressstatus' => addslashes($shippingdetails['ADDRESSSTATUS']),
										'paymentrequest_0_addressstatus' => addslashes($shippingdetails['PAYMENTREQUEST_0_ADDRESSSTATUS']),
										'protectioneligibility' => addslashes($resArray['PROTECTIONELIGIBILITY']),
										'paytime' => addslashes($resArray['TIMESTAMP']),
										'amt' => addslashes($resArray['AMT']),
										'currencycode' => addslashes($resArray['CURRENCYCODE']),
										'paymentstatus' => addslashes($resArray['PAYMENTSTATUS']),
										'firstname' => addslashes($shippingdetails['FIRSTNAME']),
										'lastname' => addslashes($shippingdetails['LASTNAME']),
										'shiptocountrycode' => addslashes($shippingdetails['SHIPTOCOUNTRYNAME']),
										'shiptostate' => addslashes($shippingdetails['SHIPTOSTATE']),
										'shiptocity' => addslashes($shippingdetails['SHIPTOCITY']),
										'shiptostreet' => addslashes($shippingdetails['SHIPTOSTREET']),
										'shiptostreet2' => addslashes($shippingdetails['SHIPTOSTREET2']),
										'shiptozip' => addslashes($shippingdetails['SHIPTOZIP']),
										'shiptophonenum' => addslashes($shippingdetails['SHIPTOPHONENUM']),
										'addtime' => gmtime()
									 );
			$GLOBALS['db']->autoExecute(ORDERPAYPALINFO, $order_paypal_info, 'INSERT');
		}
		return $resArray;
	}

	/**
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	  * hash_call: Function to perform the API call to PayPal using API signature
	  * @methodName is name of API  method.
	  * @nvpStr is nvp string.
	  * returns an associtive array containing the response from the server.
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	*/
	function hash_call($methodName,$nvpStr)
	{
		//declaring of global variables
		global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
		global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
		global $gv_ApiErrorURL;
		global $sBNCode;

		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);

	    //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
	   //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php
		if($USE_PROXY)
			curl_setopt ($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT);

		//NVPRequest for submitting to server
		$nvpreq="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($sBNCode). "&HDRBORDERCOLOR=399AE9&HDRBACKCOLOR=399AE9&PAYFLOWCOLOR=399AE9" ;
		//($nvpreq);
		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

		//getting response from server
		$response = curl_exec($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray=deformatNVP($response);
		$nvpReqArray=deformatNVP($nvpreq);
		$_SESSION['nvpReqArray']=$nvpReqArray;

		if (curl_errno($ch))
		{
			// moving to display page to display curl errors
			  $_SESSION['curl_error_no']=curl_errno($ch) ;
			  $_SESSION['curl_error_msg']=curl_error($ch);

			  //Execute the Error handling module to display errors.
		}
		else
		{
			 //closing the curl
		  	curl_close($ch);
		}

		return $nvpResArray;
	}

	/*'----------------------------------------------------------------------------------
	 Purpose: Redirects to PayPal.com site.
	 Inputs:  NVP string.
	 Returns:
	----------------------------------------------------------------------------------
	*/
	function RedirectToPayPal ( $token )
	{
		global $PAYPAL_URL;

		// Redirect to paypal.com here
		$payPalURL = $PAYPAL_URL . $token;
		header("Location: ".$payPalURL);
	}


	/*'----------------------------------------------------------------------------------
	 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
	  * It is usefull to search for a particular key and displaying arrays.
	  * @nvpstr is NVPString.
	  * @nvpArray is Associative Array.
	   ----------------------------------------------------------------------------------
	  */
	function deformatNVP($nvpstr)
	{
		$intial=0;
	 	$nvpArray = array();

		while(strlen($nvpstr))
		{
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
	     }
		return $nvpArray;
	}

?>