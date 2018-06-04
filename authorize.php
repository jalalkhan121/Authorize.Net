<?php
/**
 * @package Authorize
 */
/*
Plugin Name: Authorize Credit Card
Plugin URI: http://www.virtuenetz.com
Description: developed by Virtuenetz.Com
Version: 1.0
Author: Jalal Khan
Author URI: http://www.virtuenetz.com
License: abc or later
Text Domain: Authorize
*/

define( 'AUTHORIZEJK_VERSION', '1.0' );
define( 'AUTHORIZEJK_MINIMUM_WP_VERSION', '3.7' );
define( 'AUTHORIZEJK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AUTHORIZEJK_DELETE_LIMIT', 100000 );
// create db tables
global $jk_db_version;
$jk_db_version = '1.0';
function authorizejk_install() {
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . 'payment';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`fname` varchar(255) NOT NULL,
	  `lname` varchar(255) NOT NULL,
	  `user_id` int(11) NOT NULL,
	  `amount` float NOT NULL,
	  `card` varchar(255) NOT NULL,
	  `expiry` varchar(10) NOT NULL,
	  `code` varchar(4) NOT NULL,
	  `date_created` datetime NOT NULL,
	  `status` varchar(10) NOT NULL,
	  `subscribe` varchar(30) NOT NULL,
	  `res_code` int(11) NOT NULL,
	  `auth_code` varchar(12) NOT NULL,
	  `trans_code` varchar(30) NOT NULL,
	  `gcode` int(11) NOT NULL,
	  `res_des` varchar(255) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'jk_db_version', $jk_db_version );
}
register_activation_hook( __FILE__, 'authorizejk_install' );
//----------------------authorize payment stuff-------------------------
  use net\authorize\api\contract\v1 as AnetAPI;
  use net\authorize\api\controller as AnetController;
  date_default_timezone_set('America/Los_Angeles');
  define("AUTHORIZENET_LOG_FILE", "phplog");
//------------- remove console or jquery error start -----------------------
add_action( 'wp_default_scripts', function( $scripts ) {
    if ( ! empty( $scripts->registered['jquery'] ) ) {
        $scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
    }
} );
//------------- remove console or jquery error end -----------------------

add_shortcode('authorize_credit_card', 'charge_credit_card');
//4111111111111111
// include js files
function my_scripts_method() {
    wp_enqueue_script(
        'script-name1',
		'https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js',
        array( 'jquery' )
    );
    wp_enqueue_script(
        'script-name2',
        plugins_url('jquery.creditCardValidator.js', __FILE__),
        array( 'jquery' )
    );
	wp_enqueue_script(
        'script-name3',
        plugins_url('js/valid8.js', __FILE__),
        array( 'jquery' )
    );

}
add_action( 'wp_enqueue_scripts', 'my_scripts_method' );
// include css files
function wpse_load_plugin_css() {
    $plugin_url = plugin_dir_url( __FILE__ );

    wp_enqueue_style( 'style1', $plugin_url . 'css/style1.css' );
   	wp_enqueue_style( 'style2', $plugin_url . 'css/jquerysctipttop.css' );
}
add_action( 'wp_enqueue_scripts', 'wpse_load_plugin_css' );


////////////////  once paid ///////////////
add_shortcode('sc_once_paid_amount', 'once_paid_amount');
function once_paid_amount() {
	
if ( is_user_logged_in() ) {
	
	 require 'vendor/autoload.php';

  define("AUTHORIZENET_LOG_FILE", "phplog");	
	
 function cancelSubscriptionjk($subscriptionId) {
	 //  require 'vendor/autoload.php';
 // define("AUTHORIZENET_LOG_FILE", "phplog");
	 global $wpdb;
	 
	 $current_user = wp_get_current_user();
    $username = $current_user->user_login;
	$user_email = $current_user->user_email;
	$user_firstname = $current_user->user_firstname;
	$user_lastname = $current_user->user_lastname;
	$user_id = $current_user->ID;
    // get ID and trans key from db
	//--------------------------------------------
	$authorize_login_id = get_option('authorize_login_id');
	$authorize_transaction_key = get_option('authorize_transaction_key');

    // Common Set Up for API Credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($authorize_login_id);/////////////////------ MERCHANT_LOGIN_ID 7RFn23t2BJ6
    $merchantAuthentication->setTransactionKey($authorize_transaction_key);//------ MERCHANT_TRANSACTION_KEY 8p429Y7Cq4h9sQBS
    $refId = 'ref' . time();

    $request = new AnetAPI\ARBCancelSubscriptionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId($refId);
    $request->setSubscriptionId($subscriptionId);

    $controller = new AnetController\ARBCancelSubscriptionController($request);

    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);

    if (($response != null) && ($response->getMessages()->getResultCode() == "Ok"))
    {
        $successMessages = $response->getMessages()->getMessage();
        //echo "SUCCESS : " . $successMessages[0]->getCode() . "  " .$successMessages[0]->getText() . "\n";
		echo '<h2 style="color:#7066ce">Thank you! Your request has been processed</h2>';
		echo '<p>You have been unsubscribe successfully</p>';
		$monthpaid = !empty(get_option('authorize_monthly_paid')) ? get_option('authorize_monthly_paid') : 75;
		echo '<p>Your credit card will not be automatically debited $'.$monthpaid.' a month.</p>';
		echo '<br /><br /><p><a href="'.site_url().'/dashboard">Click here</a> to go to your dashboard</p>';
		
		$table_name = $wpdb->prefix . 'payment';
		$wpdb->update( 
				$table_name, 
				array( 
					'status' => 'Inactive',	// string
				), 
				array( 'subscribe' => $subscriptionId ), 
				array( 
					'%s',	// value1
				), 
				array( '%d' ) 
			);
		echo '<script>function hideFrm() { document.getElementById("jkfrm").style.display="none"; }</script>';	
        
     }
    else
    {
        echo "ERROR :  Invalid response\n";
        $errorMessages = $response->getMessages()->getMessage();
        echo "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
        
    }

    return $response;

  }	
	
	

// once paid
function chargeCreditCardjk($peram){
	  global $wpdb;
    // get ID and trans key from db
	//--------------------------------------------
	$authorize_login_id = get_option('authorize_login_id');
	$authorize_transaction_key = get_option('authorize_transaction_key');
	if(!empty($peram[4])) {
		$namarr = explode(" ",$peram[4]);
		$fname = $namarr[0];
		$lname = $namarr[1];
	} else {
		$fname = "";
		$lname = "";
		}
	$current_user = wp_get_current_user();
    $username = $current_user->user_login;
	$user_email = $current_user->user_email;
	$user_firstname = $current_user->user_firstname;
	$user_lastname = $current_user->user_lastname;
	$user_id = $current_user->ID;
	//--------------------------------------------
	// Common setup for API credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($authorize_login_id);/////////////////------ MERCHANT_LOGIN_ID 7RFn23t2BJ6
    $merchantAuthentication->setTransactionKey($authorize_transaction_key);//------ MERCHANT_TRANSACTION_KEY 8p429Y7Cq4h9sQBS
    $refId = 'ref' . time();

    // Create the payment data for a credit card
    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber($peram[1]); //////--- card no e.g. 4111111111111111
    $creditCard->setExpirationDate($peram[2]); //////////////--- expiry 1220
    $creditCard->setCardCode($peram[3]);//////////////////////--- card code 123
    $paymentOne = new AnetAPI\PaymentType();
    $paymentOne->setCreditCard($creditCard);

    $order = new AnetAPI\OrderType();
    $order->setDescription("Monthly Payment");

    // Set the customer's Bill To address
    $customerAddress = new AnetAPI\CustomerAddressType();
    $customerAddress->setFirstName($fname);
    $customerAddress->setLastName($lname);
    $customerAddress->setCompany("");
    $customerAddress->setAddress("");
    $customerAddress->setCity("");
    $customerAddress->setState("");
    $customerAddress->setZip("");
    $customerAddress->setCountry("");

    // Set the customer's identifying information
    $customerData = new AnetAPI\CustomerDataType();
    $customerData->setType("individual");
    $customerData->setId(""); // Customer ID 
    $customerData->setEmail("");

    //Add values for transaction settings
    $duplicateWindowSetting = new AnetAPI\SettingType();
    $duplicateWindowSetting->setSettingName("duplicateWindow");
    $duplicateWindowSetting->setSettingValue("600");

    // Create a TransactionRequestType object
    $transactionRequestType = new AnetAPI\TransactionRequestType();
    $transactionRequestType->setTransactionType( "authCaptureTransaction"); 
    $transactionRequestType->setAmount($peram[0]);
    $transactionRequestType->setOrder($order);
    $transactionRequestType->setPayment($paymentOne);
    $transactionRequestType->setBillTo($customerAddress);
    $transactionRequestType->setCustomer($customerData);
    $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);

    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId( $refId);
    $request->setTransactionRequest( $transactionRequestType);

    $controller = new AnetController\CreateTransactionController($request);
    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
    

    if ($response != null)
    {
      if($response->getMessages()->getResultCode() == 'Ok')
      {
        $tresponse = $response->getTransactionResponse();
        
        if ($tresponse != null && $tresponse->getMessages() != null)   
        {
			$res_code = $tresponse->getResponseCode();
			$auth_code = $tresponse->getAuthCode();
			$trans_code = $tresponse->getTransId();
			$gcode = $tresponse->getMessages()[0]->getCode();
			$res_des = $tresponse->getMessages()[0]->getDescription();
         
		 /* echo " Transaction Response Code : " . $res_code . "\n";
          echo " Successfully created an authCapture transaction with Auth Code : " . $auth_code . "\n";
          echo " Transaction ID : " . $trans_code . "\n";
          echo " Code : " . $ccode . "\n"; 
          echo " Description : " . $res_des . "\n";*/
		$oncepaid = !empty(get_option('authorize_once_paid')) ? get_option('authorize_once_paid') : 1500;  
		echo '<h2 style="color:#7066ce">Thank you! '.$fname.'\'s payment has been processed</h2>';
		echo '<p>'.$fname.'\'s credit card has been charged <span style="color:#6dcd62; font-size:28px">$'.$oncepaid.'</span></p>';
		echo '<p>Congratulations....!! you have been hired as a perfect cNanny successfully.</p>';
		echo '<br /><br /><p><a href="'.site_url().'/dashboard">Click here</a> to go to your dashboard</p>';


		  //---------------------------------------------------
		
			$table_name = $wpdb->prefix . 'payment';	
			$wpdb->insert($table_name, array(
			'fname' => $fname,
			'lname' => $lname,
			'user_id' => $user_id,
			'amount' => $peram[0],
			'card' => base64_encode($peram[1]),
			'expiry' => $peram[2],
			'code' => base64_encode($peram[3]),
			'date_created' => current_time('mysql', 1),
			'status' => 'Active',
			'subscribe' => '-',
			'res_code' => $res_code,
			'auth_code' => $auth_code,
			'trans_code' => $trans_code,
			'gcode' => $gcode,
			'res_des' => $res_des,
		));	
		
		echo '<script>function hideFrm() { document.getElementById("jkfrm").style.display="none"; }</script>';
		  //------------------------------------------------------
		  
        }
        else
        {
          echo "Transaction Failed \n";
          if($tresponse->getErrors() != null)
          {
            echo " Error code  : " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
            echo " Error message : " . $tresponse->getErrors()[0]->getErrorText() . "\n";            
          }
        }
      }
      else
      {
        echo "Transaction Failed \n";
        $tresponse = $response->getTransactionResponse();
        
        if($tresponse != null && $tresponse->getErrors() != null)
        {
          echo " Error code  : " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
          echo " Error message : " . $tresponse->getErrors()[0]->getErrorText() . "\n";                      
        }
        else
        {
          echo " Error code  : " . $response->getMessages()->getMessage()[0]->getCode() . "\n";
          echo " Error message : " . $response->getMessages()->getMessage()[0]->getText() . "\n";
        }
      }      
    }
    else
    {
      echo  "No response returned \n";
    }

    return $response;
  }
  
  
  global $wpdb;
   	$current_user = $_GET['pid'];
	$user_id = $current_user;
  	$table_name = $wpdb->prefix . 'payment'; 
	$row = $wpdb->get_row( "SELECT * FROM $table_name where user_id = ".$user_id." and status = 'Active' and subscribe != '-' order by id desc" );
	
	$subscribe = $row->subscribe;
	$card = base64_decode($row->card);
	$exp = $row->expiry;
	$code = base64_decode($row->code);
	$fullname = $row->fname." ".$row->lname;
	
		if($subscribe > 0)
		cancelSubscriptionjk($subscribe);
  // pay once $1500  (run on both authorize live and test mode)
  $oncepaid = !empty(get_option('authorize_once_paid')) ? get_option('authorize_once_paid') : 1500;
  		$amount = $oncepaid;
	  	$peramaters = array($amount,$card,$exp,$code,$fullname);
  		chargeCreditCardjk($peramaters);
  
  
 }
}
  ////////////////////////////////////////////



//charge credit card using authorize.net payment
function charge_credit_card() {

if ( is_user_logged_in() ) {
	
	 require 'vendor/autoload.php';

  define("AUTHORIZENET_LOG_FILE", "phplog");
  
  ///////////////////////------- FUNCTION CANCEL SUBSCRIPTION --------////////////////////////////
  
  function cancelSubscription($subscriptionId) {
	 //  require 'vendor/autoload.php';
 // define("AUTHORIZENET_LOG_FILE", "phplog");
	 global $wpdb;
	 
	 $current_user = wp_get_current_user();
    $username = $current_user->user_login;
	$user_email = $current_user->user_email;
	$user_firstname = $current_user->user_firstname;
	$user_lastname = $current_user->user_lastname;
	$user_id = $current_user->ID;
    // get ID and trans key from db
	//--------------------------------------------
	$authorize_login_id = get_option('authorize_login_id');
	$authorize_transaction_key = get_option('authorize_transaction_key');

    // Common Set Up for API Credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($authorize_login_id);/////////////////------ MERCHANT_LOGIN_ID 7RFn23t2BJ6
    $merchantAuthentication->setTransactionKey($authorize_transaction_key);//------ MERCHANT_TRANSACTION_KEY 8p429Y7Cq4h9sQBS
    $refId = 'ref' . time();

    $request = new AnetAPI\ARBCancelSubscriptionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId($refId);
    $request->setSubscriptionId($subscriptionId);

    $controller = new AnetController\ARBCancelSubscriptionController($request);

    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);

    if (($response != null) && ($response->getMessages()->getResultCode() == "Ok"))
    {
        $successMessages = $response->getMessages()->getMessage();
        //echo "SUCCESS : " . $successMessages[0]->getCode() . "  " .$successMessages[0]->getText() . "\n";
		echo '<h2 style="color:#7066ce">Thank you! Your request has been processed</h2>';
		echo '<p>You have been unsubscribe successfully</p>';
		$monthpaid = !empty(get_option('authorize_monthly_paid')) ? get_option('authorize_monthly_paid') : 75;
		echo '<p>Your credit card will not be automatically debited $'.$monthpaid.' a month.</p>';
		echo '<br /><br /><p><a href="'.site_url().'/dashboard">Click here</a> to go to your dashboard</p>';
		
		$table_name = $wpdb->prefix . 'payment';
		$wpdb->update( 
				$table_name, 
				array( 
					'status' => 'Inactive',	// string
				), 
				array( 'subscribe' => $subscriptionId ), 
				array( 
					'%s',	// value1
				), 
				array( '%d' ) 
			);
		echo '<script>function hideFrm() { document.getElementById("jkfrm").style.display="none"; }</script>';	
        
     }
    else
    {
        echo "ERROR :  Invalid response\n";
        $errorMessages = $response->getMessages()->getMessage();
        echo "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
        
    }

    return $response;

  }
  
///////////////////------------- SUBSCRIPTION USERS -----------------/////////////////////
  function createSubscription($peram){
	  global $wpdb;
    // get ID and trans key from db
	//--------------------------------------------
	$authorize_login_id = get_option('authorize_login_id');
	$authorize_transaction_key = get_option('authorize_transaction_key');
	if(!empty($peram[4])) {
		$namarr = explode(" ",$peram[4]);
		$fname = $namarr[0];
		$lname = $namarr[1];
	} else {
		$fname = "";
		$lname = "";
		}
	$intervalLength = $peram[0];
	//------------------------------------
	$current_user = wp_get_current_user();
    $username = $current_user->user_login;
	$user_email = $current_user->user_email;
	$user_firstname = $current_user->user_firstname;
	$user_lastname = $current_user->user_lastname;
	$user_id = $current_user->ID;	
	//--------------------------------------------
	// Common setup for API credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($authorize_login_id);/////////////////------ MERCHANT_LOGIN_ID 7RFn23t2BJ6
    $merchantAuthentication->setTransactionKey($authorize_transaction_key);//------ MERCHANT_TRANSACTION_KEY 8p429Y7Cq4h9sQBS
    $refId = 'ref' . time();

   // Subscription Type Info
    $subscription = new AnetAPI\ARBSubscriptionType();
    $subscription->setName("Cnanny Monthly Subscription");

    $interval = new AnetAPI\PaymentScheduleType\IntervalAType();
    $interval->setLength($intervalLength);
    $interval->setUnit("days");

    $paymentSchedule = new AnetAPI\PaymentScheduleType();
    $paymentSchedule->setInterval($interval);
    $paymentSchedule->setStartDate(new DateTime(date('Y-m-d')));
    $paymentSchedule->setTotalOccurrences("12");
    $paymentSchedule->setTrialOccurrences("10");

    $subscription->setPaymentSchedule($paymentSchedule);
    $subscription->setAmount($peram[0]);
    $subscription->setTrialAmount("0.00");
    
    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber($peram[1]);    //////--- card no e.g. 4111111111111111
    $creditCard->setExpirationDate($peram[2]); //////////////--- expiry 1220

    $payment = new AnetAPI\PaymentType();
    $payment->setCreditCard($creditCard);
    $subscription->setPayment($payment);

    $order = new AnetAPI\OrderType();
    $order->setInvoiceNumber("1234354");        
    $order->setDescription("Cnanny Monthly Subscription"); 
    $subscription->setOrder($order); 
    
    $billTo = new AnetAPI\NameAndAddressType();
    $billTo->setFirstName($fname);
    $billTo->setLastName($lname);

    $subscription->setBillTo($billTo);

    $request = new AnetAPI\ARBCreateSubscriptionRequest();
    $request->setmerchantAuthentication($merchantAuthentication);
    $request->setRefId($refId);
    $request->setSubscription($subscription);
    $controller = new AnetController\ARBCreateSubscriptionController($request);

    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
    
    if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
    {
		//$response->getSubscriptionId()
		echo '<h2 class="page-heading">Thank you! Your payment has been processed</h2>';
		$monthpaid = !empty(get_option('authorize_monthly_paid')) ? get_option('authorize_monthly_paid') : 75;
		echo '<div class="clearfix charged"><span>Your credit card has been charged <span class="text-green">$'.$monthpaid.'</span></span><br />';
		
		$oncepaid = !empty(get_option('authorize_once_paid')) ? get_option('authorize_once_paid') : 1500;
		echo '<div class="clearfix charged">
							<p>Your credit card will be automatically debited $'.$monthpaid.' a month until you hire a cNanny  or <a href="'.site_url().'/subscription-cancellation" class="text-black text-underline">cancel your subscription</a>.</p>
							<p>
								When you hire your perfect cNanny, you will be charged a one-time placement fee of $'.$oncepaid.' only after she accepts the offer.
								Your monthly subscription will then be automatically cancelled.
							</p>
						</div>';
		echo '<br /><br /><p><a href="'.site_url().'/dashboard">Click here</a> to go to your dashboard</p>';
		
		$subscrId = $response->getSubscriptionId();
		
		$table_name = $wpdb->prefix . 'payment';	
			$ins = $wpdb->insert($table_name, array(
			'fname' => $fname,
			'lname' => $lname,
			'user_id' => $user_id,
			'amount' => $peram[0],
			'card' => base64_encode($peram[1]),
			'expiry' => $peram[2],
			'code' => '',
			'date_created' => current_time('mysql', 1),
			'status' => 'Active',
			'subscribe' => $subscrId,
			'res_code' => '-',
			'auth_code' => '-',
			'trans_code' => '-',
			'gcode' => '-',
			'res_des' => 'Success',
		));	
		$paymentsuccess = 1;
		echo '<script>function hideFrm() { document.getElementById("jkfrm").style.display="none"; }</script>';
     }
    else
    {
        echo "ERROR :  Invalid response\n";
        $errorMessages = $response->getMessages()->getMessage();
        echo "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
		$paymentsuccess = 1;
    }

    return $paymentsuccess;
  }
  ///////////////////------------- ONCE PAID USERS -----------------/////////////////////
   function chargeCreditCard($peram){
	  global $wpdb;
    // get ID and trans key from db
	//--------------------------------------------
	$authorize_login_id = get_option('authorize_login_id');
	$authorize_transaction_key = get_option('authorize_transaction_key');
	if(!empty($peram[4])) {
		$namarr = explode(" ",$peram[4]);
		$fname = $namarr[0];
		$lname = $namarr[1];
	} else {
		$fname = "";
		$lname = "";
		}
	$current_user = wp_get_current_user();
    $username = $current_user->user_login;
	$user_email = $current_user->user_email;
	$user_firstname = $current_user->user_firstname;
	$user_lastname = $current_user->user_lastname;
	$user_id = $current_user->ID;
	//--------------------------------------------
	// Common setup for API credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($authorize_login_id);/////////////////------ MERCHANT_LOGIN_ID 7RFn23t2BJ6
    $merchantAuthentication->setTransactionKey($authorize_transaction_key);//------ MERCHANT_TRANSACTION_KEY 8p429Y7Cq4h9sQBS
    $refId = 'ref' . time();

    // Create the payment data for a credit card
    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber($peram[1]); //////--- card no e.g. 4111111111111111
    $creditCard->setExpirationDate($peram[2]); //////////////--- expiry 1220
    $creditCard->setCardCode($peram[3]);//////////////////////--- card code 123
    $paymentOne = new AnetAPI\PaymentType();
    $paymentOne->setCreditCard($creditCard);

    $order = new AnetAPI\OrderType();
    $order->setDescription("Monthly Payment");

    // Set the customer's Bill To address
    $customerAddress = new AnetAPI\CustomerAddressType();
    $customerAddress->setFirstName($fname);
    $customerAddress->setLastName($lname);
    $customerAddress->setCompany("");
    $customerAddress->setAddress("");
    $customerAddress->setCity("");
    $customerAddress->setState("");
    $customerAddress->setZip("");
    $customerAddress->setCountry("");

    // Set the customer's identifying information
    $customerData = new AnetAPI\CustomerDataType();
    $customerData->setType("individual");
    $customerData->setId(""); // Customer ID 
    $customerData->setEmail("");

    //Add values for transaction settings
    $duplicateWindowSetting = new AnetAPI\SettingType();
    $duplicateWindowSetting->setSettingName("duplicateWindow");
    $duplicateWindowSetting->setSettingValue("600");

    // Create a TransactionRequestType object
    $transactionRequestType = new AnetAPI\TransactionRequestType();
    $transactionRequestType->setTransactionType( "authCaptureTransaction"); 
    $transactionRequestType->setAmount($peram[0]);
    $transactionRequestType->setOrder($order);
    $transactionRequestType->setPayment($paymentOne);
    $transactionRequestType->setBillTo($customerAddress);
    $transactionRequestType->setCustomer($customerData);
    $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);

    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId( $refId);
    $request->setTransactionRequest( $transactionRequestType);

    $controller = new AnetController\CreateTransactionController($request);
    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
    

    if ($response != null)
    {
      if($response->getMessages()->getResultCode() == 'Ok')
      {
        $tresponse = $response->getTransactionResponse();
        
        if ($tresponse != null && $tresponse->getMessages() != null)   
        {
			$res_code = $tresponse->getResponseCode();
			$auth_code = $tresponse->getAuthCode();
			$trans_code = $tresponse->getTransId();
			$gcode = $tresponse->getMessages()[0]->getCode();
			$res_des = $tresponse->getMessages()[0]->getDescription();
         
		 /* echo " Transaction Response Code : " . $res_code . "\n";
          echo " Successfully created an authCapture transaction with Auth Code : " . $auth_code . "\n";
          echo " Transaction ID : " . $trans_code . "\n";
          echo " Code : " . $ccode . "\n"; 
          echo " Description : " . $res_des . "\n";*/
		  
		echo '<h2 style="color:#7066ce">Thank you! Your payment has been processed</h2>';
		$oncepaid = !empty(get_option('authorize_once_paid')) ? get_option('authorize_once_paid') : 1500;
		echo '<p>Your credit card has been charged <span style="color:#6dcd62; font-size:28px">$'.$oncepaid.'</span></p>';
		echo '<p>you have been hired your perfect cNanny successfully.</p>';
		echo '<br /><br /><p><a href="'.site_url().'/dashboard">Click here</a> to go to your dashboard</p>';


		  //---------------------------------------------------
		
			$table_name = $wpdb->prefix . 'payment';	
			$wpdb->insert($table_name, array(
			'fname' => $fname,
			'lname' => $lname,
			'user_id' => $user_id,
			'amount' => $peram[0],
			'card' => base64_encode($peram[1]),
			'expiry' => $peram[2],
			'code' => base64_encode($peram[3]),
			'date_created' => current_time('mysql', 1),
			'status' => 'Active',
			'subscribe' => '-',
			'res_code' => $res_code,
			'auth_code' => $auth_code,
			'trans_code' => $trans_code,
			'gcode' => $gcode,
			'res_des' => $res_des,
		));	
		
		echo '<script>function hideFrm() { document.getElementById("jkfrm").style.display="none"; }</script>';
		  //------------------------------------------------------
		  
        }
        else
        {
          echo "Transaction Failed \n";
          if($tresponse->getErrors() != null)
          {
            echo " Error code  : " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
            echo " Error message : " . $tresponse->getErrors()[0]->getErrorText() . "\n";            
          }
        }
      }
      else
      {
        echo "Transaction Failed \n";
        $tresponse = $response->getTransactionResponse();
        
        if($tresponse != null && $tresponse->getErrors() != null)
        {
          echo " Error code  : " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
          echo " Error message : " . $tresponse->getErrors()[0]->getErrorText() . "\n";                      
        }
        else
        {
          echo " Error code  : " . $response->getMessages()->getMessage()[0]->getCode() . "\n";
          echo " Error message : " . $response->getMessages()->getMessage()[0]->getText() . "\n";
        }
      }      
    }
    else
    {
      echo  "No response returned \n";
    }

    return $response;
  }
 
 ///////////////////////////////--------- FUNCTION END -----------/////////////////////////////////// 
if(!empty($_POST['amount'])) {
  $payment_type = $_POST['payment_type'];
  $card = $_POST['card'];
  $mn = $_POST['mn'];
  $yr = $_POST['yr'];
  $exp = $mn.''.$yr;
  $code = $_POST['code'];
  $fullname = $_POST['fullname'];
  $interval = 30; // 30 days interval
  // monthly subscription $75 (run on authorize live mode only)
  if(isset($payment_type) && $payment_type == "monthly") {
	  $monthpaid = !empty(get_option('authorize_monthly_paid')) ? get_option('authorize_monthly_paid') : 75;
	  	 $amount = $monthpaid;
  		$peramaters = array($amount,$card,$exp,$code,$fullname,$interval);
    	$ret = createSubscription($peramaters);
		
		//print_r($ret); exit(0);
 	 } 
  else { 
  // cancel monthly subscription first
   global $wpdb;
   	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;
  	$table_name = $wpdb->prefix . 'payment'; 
	$subscribe = $wpdb->get_var( "SELECT subscribe FROM $table_name where user_id = ".$user_id." and status = 'Active' and subscribe != '-' order by id desc" );
		if($subscribe > 0)
		cancelSubscription($subscribe);
  // pay once $1500  (run on both authorize live and test mode)
  $oncepaid = !empty(get_option('authorize_once_paid')) ? get_option('authorize_once_paid') : 1500;
  		$amount = $oncepaid;
	  	$peramaters = array($amount,$card,$exp,$code,$fullname);
  		chargeCreditCard($peramaters);
  		}

}
/////////////////////////////////////////////////////////////////////////

if(isset($_GET['subid']) && $_GET['subid'] > 0) { 
 
	$subid = $_GET['subid'];
	cancelSubscription($subid); 
}




/* global $wpdb;
   $table_name = $wpdb->prefix . 'payment';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);	*/
	
$current_user = wp_get_current_user();
$username = $current_user->user_login;
$user_email = $current_user->user_email;
$user_firstname = $current_user->user_firstname;
$user_lastname = $current_user->user_lastname;
$user_id = $current_user->ID;
$thefullname = $user_firstname?$user_firstname.' '.$user_lastname:'';	
  ?>
 <style>
	 #sjkfrm input[type=text] {
		 border:solid 1px #666666;
		border-radius: 15px;
		border: 2px solid #666666;
		padding: 5px; 
		height: 50px; 
		 }
	.form-control{
		border: 2px solid #999 !important;
		border-radius: 8px;
		height:40px !important;
		width: 70%;
		}
	.thimg{
		width: 70%;
		}	
	@media only screen and (max-width: 700px) {
		.form-control{
			width: 100%;
		}
		.thimg{
		width: 100%;
		}
		}	
	.selbx{
		border: 2px solid #999 !important;
		height:40px !important;
		}	 
	.sbmt{
		background-color:#6dcd62;
		color:#FFF;
		padding:12px 35px;
		border-radius: 8px;
		border: none 0px;
		margin-bottom: 20px;
		}
	.sbmt:hover{
		background-color:#090;
		}	
 </style>
 <?php 
 /* global $wpdb;
   $table_name = $wpdb->prefix . 'payment';
 $oncePaid = $wpdb->get_var( "SELECT amount FROM $table_name where user_id = ".$user_id." and status = 'Active' and subscribe == '-' order by id desc" );
 echo $oncePaid; exit(0);
 if($oncePaid == 1000) {
	 ?>
     <h2 style="color:#7066ce">You have already paid $1500 for perfect cNanny</h2><br />
    <p>You have already paid $1500 for perfect cNanny. so you need not to be subscribed again</p>
     <?php
	 $disp = 'style="display:none"';
	 } else {
	$disp = 'style="display:block"';	 
	}*/
 ?> 
<form action="" id="jkfrm" method="post" enctype="multipart/form-data" name="payment" autocomplete="off" onsubmit="check_exp()" <?php //echo $disp; ?>>
	<style type="text/css">
    #checkout_card_number {
      background-image: url('<?php echo WP_PLUGIN_URL; ?>/authorizejk/cards.png');
      background-position: 3px 3px;
      background-size: 40px 252px; /* 89 x 560 */
      background-repeat: no-repeat;
      padding-left: 48px !important;
    }
	label span {
		font-size:12px;
		font-weight:600 !important;
		color:#666 !important;
		}
	/*input.pw {
    -webkit-text-security: disc;
	}*/
	
	
	


#username,
#pw {
    display: inline-block;
    width: 150px;
    background: #FFF;
   	border: 2px solid #999 !important;
    border-radius: 8px;
    height: 40px !important;
	line-height: 40px;
    padding: 0px 5px;
    letter-spacing: 2px;
	overflow:hidden;
}
#pw {
    -webkit-text-security: disc;
	
}


    </style>
    <?php
	 global $wpdb;
   $table_name = $wpdb->prefix . 'payment'; 
	$subscribe = $wpdb->get_var( "SELECT subscribe FROM $table_name where user_id = ".$user_id." and status = 'Active' and subscribe != '-' order by id desc" );
	?>
    <!--<h2 style="color:#6dcd62; font-weight:bold;">$75/month</h2><br />-->
    <?php if(!empty($subscribe)) { 
	if(empty($ret)) {
		wp_redirect(site_url().'/already-subscribe'); exit(0);
	}
	?>

    <small>Don't worry, you can <a href="?subid=<?php echo $subscribe; ?>" onclick="return confirm('Are you sure that you want to cancel monthly subscription from Cnanny?')">cancel</a> at any time</small><br />
    
     <?php $oncepaid = !empty(get_option('authorize_once_paid')) ? get_option('authorize_once_paid') : 1500; ?>
    <input type="radio" name="payment_type" value="yearly" checked="checked" style="clear:both" /> <label style="margin-top:20px;"> Pay Once $<?php echo $oncepaid; ?> for hiring perfect cNanny</label>
    <?php } else { ?>
    <?php $monthpaid = !empty(get_option('authorize_monthly_paid')) ? get_option('authorize_monthly_paid') : 75; ?>
    <br />
    <input type="radio" name="payment_type" value="monthly" checked="checked" /> <label> Monthly Subscribe $<?php echo $monthpaid; ?></label>
    <?php } ?>
    <br  /><br />
    <img src="<?php echo WP_PLUGIN_URL; ?>/authorizejk/cclogos.gif" class="thimg" /><br /><br />
    <label style="">Full Name <span>(as it appears on your card)</span></label> <input type="text" name="fullname" maxlength="80" required="required" class="input-text form-control validate-alpha required" placeholder="" autocomplete="off" value="<?php echo $thefullname; ?>" />
    <input type="hidden" name="amount" value="<?php echo $monthpaid; ?>" /><br />
     <label>Card Number <span>(no dashes or spaces)</span></label><input id="checkout_card_number" name="card" required="required" class="input-text form-control validate-creditcard required" type="text" maxlength="16" data-stripe="number" placeholder="" autocomplete="off"><br />
    <script type="text/javascript">
	$ = jQuery.noConflict();
        var $cardinput = $('#checkout_card_number');
        $('#checkout_card_number').validateCreditCard(function(result)
        {		
            //console.log(result);
            if (result.card_type != null)
            {				
                switch (result.card_type.name)
                {
                    case "visa":
                        $cardinput.css('background-position', '3px -34px');
                        $cardinput.addClass('card_visa');
                        break;
    
                    case "visa_electron":
                        $cardinput.css('background-position', '3px -72px');
                        $cardinput.addClass('card_visa_electron');
                        break;
    
                    case "mastercard":
                        $cardinput.css('background-position', '3px -110px');
                        $cardinput.addClass('card_mastercard');
                        break;
    
                    case "maestro":
                        $cardinput.css('background-position', '3px -148px');
                        $cardinput.addClass('card_maestro');
                        break;
    
                    case "discover":
                        $cardinput.css('background-position', '3px -186px');
                        $cardinput.addClass('card_discover');
                        break;
    
                    case "amex":
                        $cardinput.css('background-position', '3px -223px');
                        $cardinput.addClass('card_amex');
                        break;
    
                    default:
                        $cardinput.css('background-position', '3px 3px');
                        break;					
                }
            } else {
                $cardinput.css('background-position', '3px 3px');
            }
    
            // Check for valid card numbere - only show validation checks for invalid Luhn when length is correct so as not to confuse user as they type.
            if (result.length_valid || $cardinput.val().length > 16)
            {
                if (result.luhn_valid) {
                    $cardinput.parent().removeClass('has-error').addClass('has-success');
                } else {
                    $cardinput.parent().removeClass('has-success').addClass('has-error');
                }
            } else {
                $cardinput.parent().removeClass('has-success').removeClass('has-error');
            }
    });
	//------------------------------------------------------
	function check_exp() {
	var yr = document.getElementById("yrt").value;	
	var currentTime = new Date();
	var curr_month = currentTime.getMonth() + 1;
	var theyear = <?php echo date('y'); ?>;
	if(yr == theyear) {
		var mnt = document.getElementById("mnt").value;
		if(mnt < curr_month) {
			alert('Please select a valid Expiry Date');
			}
	}
	
}

function set_type() { //
	var pw = $('#pw').text();
	document.getElementById("code").value = pw;
	//alert(pw);
	}
    
    </script>
    
    <label>Expiry Date</label><br />
    <select name="mn" class="selbx required" id="mnt" required>
    <option value="">Select Month</option>
    <option value="01">January</option>
    <option value="02">February</option>
    <option value="03">March</option>
    <option value="04">April</option>
    <option value="05">May</option>
    <option value="06">June</option>
    <option value="07">July</option>
    <option value="08">August</option>
    <option value="09">September</option>
    <option value="10">October</option>
    <option value="11">November</option>
    <option value="12">December</option>
    </select>
    <select name="yr" class="selbx required" id="yrt" required onchange="check_exp()">
    <option value="">Select Year</option>
    

    <?php 
    $y = date('y');
    for($i=1;$i<=12;$i++) {
    ?>
    <option value="<?php echo $y; ?>"><?php echo '20'.$y; ?></option>
    <?php $y++; } ?>
    </select>
    <br />
    <input type="text" name="card_number" value="0000 0000 0000 0000" style="display:none" /><br />
 
 <?php
 if (strlen(strstr($_SERVER['HTTP_USER_AGENT'], 'Firefox')) > 0) { // Firefox
    ?>
	 <label>Security Code <span>(3 digits on the back / Amex - 4 digits on the front)</span></label> <input type="password" id="codeff" name="code" maxlength="4" class="pw input-text form-control validate-digits required" placeholder="" required="required" value="" style="width:150px;" autocomplete="off" /><br />
	<?php
} else { // other browsers
  ?>
    <label>Security Code <span>(3 digits on the back / Amex - 4 digits on the front)</span></label> <input type="hidden" id="code" name="code" maxlength="4" class="pw input-text form-control validate-digits required" placeholder="" required="required" value="" style="width:150px;" autocomplete="off" /> <br /><div contenteditable id="pw"></div><br /><br />
  <?php
}
  ?>  


 

    
    
    <?php if(!empty($subscribe)) { 
	
	 $oncepaid = !empty(get_option('authorize_once_paid')) ? get_option('authorize_once_paid') : 1500; ?>
    <strong id="st">You will be charged $<?php echo $oncepaid; ?> for hiring perfect cNanny</strong><br /><br />
    <?php } else { 
	$monthpaid = !empty(get_option('authorize_monthly_paid')) ? get_option('authorize_monthly_paid') : 75;
	?>
    <strong id="st">You will be charged $<?php echo $monthpaid; ?> every month</strong><br /><br />
    <?php } ?>
    
    <!-- prevent auto save in browser-->
    <div style="display:none">
    <input type="text" name="cardinfo" id="txtUserName" value="0000 0000 0000 0000"/>
	<input type="text" name="txtPass" id="txtPass" value="9999999999"/>
    </div>
    
    
    <input type="submit" class="sbmt" value="Pay Now" onclick="set_type();" /><br />
    <strong>Don't want to pay right now?</strong> <a href="<?php echo site_url(); ?>/parent-search">Continue browsing</a><br />
    <strong>Questions?</strong> <a href="<?php echo site_url(); ?>/contact-us">Contact us</a>
    <script>document.addEventListener('contextmenu', event => event.preventDefault());
    payment.setAttribute( "autocomplete", "off" ); payment.code.setAttribute( "autocomplete", "off" );
	hideFrm(); //hide form and show success massage
    </script>
</form><br />
<script>
hideFrm(); //hide form and show success massage
</script>
  <?php
	} else {
		wp_redirect('login');
		}
}

///////////////////////////////////////////////////////////////////////////////////
////////////////-----------ADMIN SECTION START HERE------------////////////////////
///////////////////////////////////////////////////////////////////////////////////

add_action('admin_menu', 'jk_create_menu');

function jk_create_menu() {

	add_menu_page('Authorize Settings', 'Authorize Settings', 'administrator', 'authorize-settings-page', 'authorize_settings_page');
   add_action( 'admin_init', 'authorize_register_mysettings' );
    add_submenu_page(
        'authorize-settings-page',
        'Monthly Payments',
        'Monthly Payments',
        'manage_options',
        'payments',
        'payment_page_callback' );
}

function payment_page_callback(){
	global $wpdb;
if(isset($_GET['id']) && $_GET['id'] > 0) { // view detail
$id = $_GET['id'];
 $entries = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}payment WHERE id=".$id);
   //print_r($entry); die;
        foreach ($entries as $entry) {
            echo '<div class="wrap" style="background-color:#fff;">';
            ?>
            <table style="width:100%;" border='0' cellpadding='5' class="widefat">
                <tr><td colspan="10"><h3>Payment Detail</h3></td></tr>
                <tr><td colspan="10"><b> Payment detail is as follows: </b></td></tr>
                <tr class="alternate"><td width="200">Full Name </td><td><?php echo $entry->fname . " " . $entry->lname; ?></td></tr>
                <tr><td>Amount </td><td><?php echo number_format($entry->amount,2); ?></td></tr>
                <tr class="alternate"><td>Card </td><td><?php $cardnum = base64_decode($entry->card); echo 'xxxxxxxx'.substr($cardnum,8,8);
				//echo $cardnum; ?></td></tr>
                <tr><td>Status </td><td><?php echo $entry->status; ?></td></tr>
                <tr><td>Subscribe ID </td><td><?php echo $entry->subscribe; ?></td></tr>
                <tr><td>Expiry </td><td><?php echo $entry->expiry; ?></td></tr>
                <tr class="alternate"><td>Card Code </td><td><?php $cardcode = base64_decode($entry->code); echo 'xxxx'.substr($cardcode,2,2);
				 ?></td></tr>
                <tr><td>Responce Code </td><td><?php echo $entry->res_code; ?></td></tr>
                <tr class="alternate"><td>Auth Code </td><td><?php echo $entry->auth_code; ?></td></tr>
                <tr><td>Transacrion Code </td><td><?php echo $entry->trans_code; ?></td></tr>
                <tr class="alternate"><td>Responce Code 2</td><td><?php echo $entry->gcode; ?></td></tr>
                <tr><td>Responce Description</td><td><?php echo $entry->res_des; ?></td></tr>
                <tr class="alternate"><td>Date Created </td><td><?php echo date('M d, Y', strtotime($entry->date_created)); ?></td></tr>
                <?php echo $more_info; ?>
                <tr><td colspan="10">&nbsp;</td></tr>
                <tr><td colspan="10"><a href="admin.php?page=payments">< Back </a></td></tr>
            </table>
            

            <?php
            echo '</div>';
        }
		
} else { // view listing

        global $wpdb;
        $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
        $limit = 10;
        $offset = ( $pagenum - 1 ) * $limit;
        $entries = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}payment ORDER BY ID DESC LIMIT $offset, $limit");

        echo '<div class="wrap">';
        ?>
        <hr style="border-top:thin 1px #fdfdfd;" />
        <br />
        <h2>Monthly Subacription or Payment</h2>
        <?php if ($entries) { ?>
            <table class="widefat">
                <thead>
                 <!--<tr>
                       <th scope="col" colspan="4" class="manage-column column-name" style=""><h2>Contact Emails</h2></th>
                    </tr>   -->
                    <tr>
                        <th scope="col" class="manage-column column-name mailhd" style="">First Name</th>
                        <th scope="col" class="manage-column column-name mailsh" style="">Last Name</th>
                        <th scope="col" class="manage-column column-name mailsh" style="">Subscribe ID</th>
                        <th scope="col" class="manage-column column-name mailsh" style="">Status</th>
                        <th scope="col" class="manage-column column-name mailsh" style="">Transaction Code</th>
                        <th scope="col" class="manage-column column-name mailsh" style="">Amount</th>
                        <th scope="col" class="manage-column column-name mailsh" style="">Card Number</th>
                        <th scope="col" class="manage-column column-name mailhd" style="">Date</th>
                        <th scope="col" class="manage-column column-name mailsh" style="">Actions</th>
                    </tr>
                </thead>
                <tbody>


                    <?php
                    $count = 1;
                    $class = '';
                    foreach ($entries as $entry) {
                        $class = ( $count % 2 == 0 ) ? ' class="alternate"' : '';
                        ?>

                        <tr<?php echo $class; ?>>
                            <td class="mailhd"><?php echo substr($entry->fname, 0, 50); ?></td>
                            <td class="mailsh"><?php echo $entry->lname; ?></td>
                            <td class="mailsh"><?php echo $entry->subscribe; ?></td>
                            <td class="mailsh"><?php echo $entry->status; ?></td>
                            <td class="mailsh"><?php echo $entry->trans_code; ?></td>
                            <td class="mailsh">$<?php echo number_format($entry->amount,2); ?></td>
                            <td class="mailsh"><?php echo 'xxxxxxxx'.substr(base64_decode($entry->card),8,8); ?></td>
                            <td class="mailhd"><?php echo date('M d, Y', strtotime($entry->date_created)); ?></td>
                            <td class="mailsh"><a href="?page=payments&id=<?php echo $entry->id; ?>" title="View Detail">View Detail</a></td>
                        </tr>

                        <?php
                        $count++;
                    }
                    ?>
                </tbody>
            </table>
        <?php } else { ?>
            <table>
                <tr>
                    <td colspan="6">No payment is made yet</td>
                </tr>
            </table>
        <?php } 
		
        $total = $wpdb->get_var("SELECT COUNT(`id`) FROM {$wpdb->prefix}payment");
        $num_of_pages = ceil($total / $limit);
        $page_links = paginate_links(array(
            'base' => add_query_arg('pagenum', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;', 'aag'),
            'next_text' => __('&raquo;', 'aag'),
            'total' => $num_of_pages,
            'current' => $pagenum
        ));

        if ($page_links) {
            echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
        }

        echo '</div>';
       
		}
	}

function authorize_register_mysettings() {

	register_setting( 'wds-settings-group', 'authorize_login_id' );
	register_setting( 'wds-settings-group', 'authorize_transaction_key' );
	register_setting( 'wds-settings-group', 'authorize_donation_mode' );
	register_setting( 'wds-settings-group', 'authorize_once_paid' );
	register_setting( 'wds-settings-group', 'authorize_monthly_paid' );
	register_setting( 'wds-settings-group', 'authorize_thankyou_message' );
	register_setting( 'wds-settings-group', 'authorize_processor_description' );

}
// authorize plugin setting in the admin panel
function authorize_settings_page() {
?>
<div class="wrap">
<h2>Authorize Credit Card Settings</h2>

<?php
   
if( isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true'):
   echo    '<div id="setting-error-settings_updated" class="updated settings-error"> 
<p><strong>Settings saved.</strong></p></div>';
endif;
?>

<form method="post" action="options.php">
    <?php settings_fields( 'wds-settings-group' );
	 do_settings_sections( 'wds-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
	    <th scope="row">Authorize.net Login ID</th>
        <td><input type="text" style="width:50%" name="authorize_login_id" value="<?php echo get_option('authorize_login_id'); ?>" placeholder="API Login ID" /></td>
        </tr>
		
        <tr valign="top">
        <th scope="row">Authorize.net Transaction Key</th>
        <td><input type="text" style="width:50%" name="authorize_transaction_key" value="<?php echo get_option('authorize_transaction_key'); ?>" placeholder="API Transaction Key" /></td>
        </tr>
		
		<tr valign="top">
        <th scope="row">Mode(Live/Test Sandbox)</th>
        <td><select name="authorize_donation_mode" />
				<option value="live" <?php if( get_option('authorize_donation_mode') == "live" ): echo 'selected'; endif;?> >Live</option>
				<option value="test" <?php if( get_option('authorize_donation_mode') == "test" ): echo 'selected'; endif;?> >Test/Sandbox</option>
			</select></td>
		</tr>
        
         <tr valign="top">
        <th scope="row">Once paid amount (in $)</th>
        <td><input type="text" style="width:50%" name="authorize_once_paid" value="<?php echo get_option('authorize_once_paid'); ?>" placeholder="1500" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Monthly subscribe amount  (in $)</th>
        <td><input type="text" style="width:50%" name="authorize_monthly_paid" value="<?php echo get_option('authorize_monthly_paid'); ?>" placeholder="100" /></td>
        </tr>

		<tr valign="top">
        <th scope="row">Success You Message</th>
        <td><input type="text" style="width:50%" name="authorize_thankyou_message" value="<?php echo get_option('authorize_thankyou_message'); ?>" placeholder="Thank you message visible to Donor after donation" /></td>
        </tr>
		
		<?php /*?><tr valign="top">
        <th scope="row">Processor Description</th>
        <td><input type="text" style="width:50%" name="authorize_processor_description" value="<?php echo get_option('authorize_processor_description'); ?>" /></td>
        </tr><?php */?>
       
    </table>
    
    <?php submit_button(); ?>

</form>		
<p style="font-weight:bold">Use shortcode [authorize_credit_card]</p>
</div><?php
}
?>
