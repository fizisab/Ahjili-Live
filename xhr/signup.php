<?php 



if ($action == 'signup' && $config['signup_system'] == 'on') {
	$error  = false;
	$isemail = 1;
	if(isset($_POST['isemail'])) {
		$isemail = $_POST['isemail'];	
	}
	$post   = array();
	$post[] = (empty($_POST['email']));
	$post[] = (empty($_POST['password']) || empty($_POST['conf_password']));

	if (in_array(true, $post)) {
		$error = lang('please_fill_fields');
	}

	else {

		/*if (User::userNameExists($_POST['username'])) {
			$error = lang('username_is_taken');
		}

		else if(strlen($_POST['username']) < 4 || strlen($_POST['username']) > 16){
			$error = lang('username_characters_length');
		}*/

		/*else if(!preg_match('/^[\w]*[a-zA-Z]{1}[\w]*$/', $_POST['username'])){
			
				$error = lang('username_invalid_characters');
			
		}
*/
		if(User::userEmailExists($_POST['email'])){
			$error = lang('email_exists');
		}

		else if(User::userPhoneExists($_POST['email'])){
			$error = lang('phonenumber_exists');
		}
		else if($_POST['password'] != $_POST['conf_password']){
			$error = lang('password_not_match');
		}

		elseif (strlen($_POST['conf_password']) < 4) {
			$error = lang('password_is_short');
		}
		else if($isemail){
			if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
			
			$error = lang('email_invalid_characters');
			} 
		}
		else if(!$isemail) {
			$_POST['phone_number'] = $_POST['email'];
			if(!filter_var($_POST['email'], FILTER_SANITIZE_NUMBER_INT) || strlen($_POST['email']) < 8 || strlen($_POST['email']) > 20) {
			$error = lang('phone_invalid_characters');
		}

		 if(User::checkSMSlimit($_POST['phone_number']) >2){
			$error = lang('daily_limit_reached');
		}
			
	}

		
		$blacklist = $user->isInBlackList($_POST['username'],$_POST['email']);
		if ($blacklist['count'] > 0) {
			if ($blacklist['type'] == 'username') {
				$error = lang('username_in_blacklist');
			}
			elseif ($blacklist['type'] == 'email') {
				$error = lang('email_in_blacklist');
			}
			elseif ($blacklist['type'] == 'email_username') {
				$error = lang('email_username_in_blacklist');
			}
			else{
				$error = lang('ip_in_blacklist');
			}
		}
	}

	if(!empty($config['specific_email_signup'])){
		if (preg_match_all('~@(.*?)(.*)~', $_POST['email'], $matches) && !empty($matches[2]) && !empty($matches[2][0]) && $matches[2][0] !== $config['specific_email_signup']) {
            $error = str_replace('{0}',$config['specific_email_signup'] ,lang('email_provider_specific_mail'));
		}
	}
	//block specific Emails for example gmail.com users couldn't sign up
	if (preg_match_all('~@(.*?)(.*)~', $_POST['email'], $matches) && !empty($matches[2]) && !empty($matches[2][0]) && IsBanned($matches[2][0])) {
		$error = lang('email_provider_banned');
	}
	if (empty($error)) {

		if(empty($isemail)){
			session_start();
			$_SESSION["phone_number"] = $_POST['phone_number'];
			$_SESSION["password"] = $_POST['password'];
			$_SESSION["gender"] = $_POST['gender'];
			$phone = $_POST['phone_number'];
			$otp = random_int(100000, 999999);
			$_SESSION["otp"] = $otp;

			$send = User::sendotp($phone,$otp);
			if($send){
			$insertsms  = User::insertSmsTime($phone);
			$data['status']  = 225;
			$_SESSION['totalsms']  = User::checkSMSlimit($phone);
			}
				
			

		} else {
				$register = User::registerUser();
				$data['status']  = 200;
				if ($config['email_validation'] == 'on') {
				$data['message'] = lang('successfully_joined_created');
				} else {
				$data['message'] = lang('successfully_joined_desc');
				}

		}
	}
	else{
		$data['status']  = 400;
		$data['message'] = $error;
	}
}