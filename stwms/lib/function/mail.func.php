<?php

function run_log($mode='SMTP',$b='',$c='',$d=''){
	// lg($mode.':'.$b.$c.$d."\r\n", 'mail_failed_log.txt',1);
}

/**
 * 发送邮件
 *
 * @param $toemail 收件人email
 * @param $subject 邮件主题
 * @param $message 正文
 * @param $from 发件人
 * @param $cfg 邮件配置信息
 * @param $sitename 邮件站点名称
 */
function send_mail($toemail,$subject,$message,$from='',$mail=array(),$sitename=''){
	if($sitename == ''){
		$siteinfo=getcache('setting', 'setting', 'array', 'web');
		$sitename=$siteinfo['name'];
	}
	
	if($mail && is_array($mail)){
		$from=$mail['from'];
		$mail_type=$mail['type']; // 邮件发送模式
	}else{
		$mail=getcache('setting', 'setting', 'array', 'mail');
		$from=$mail['from'];
		$mail_type=$mail['type']; // 邮件发送模式
		$mail['mailsend']=2;
		$mail['maildelimiter']=1;
		$mail['mailusername']=1;
	}
	// mail 发送模式
	if($mail_type == 0){
		$headers='MIME-Version: 1.0' . "\r\n";
		$headers.='Content-type: text/html; charset=utf-8' . "\r\n";
		$headers.='From: ' . $sitename . ' <' . $from . '>' . "\r\n";
		mail($toemail, $subject, $message, $headers);
		return true;
	}
	// 邮件头的分隔符
	$maildelimiter=$mail['maildelimiter'] == 1 ? "\r\n" : ($mail['maildelimiter'] == 2 ? "\r" : "\n");
	// 收件人地址中包含用户名
	$mailusername=isset($mail['mailusername']) ? $mail['mailusername'] : 1;
	// 端口
	$mail['port']=$mail['port'] ? $mail['port'] : 25;
	$mail['mailsend']=$mail['mailsend'] ? $mail['mailsend'] : 1;
	
	// 发信者
	$email_from=$from == '' ? '=?utf-8?B?' . base64_encode($sitename) . "?= <" . $from . ">" : (preg_match('/^(.+?) \<(.+?)\>$/', $from, $mats) ? '=?utf-8?B?' . base64_encode($mats[1]) . "?= <$mats[2]>" : $from);
	
	$email_to=preg_match('/^(.+?) \<(.+?)\>$/', $toemail, $mats) ? ($mailusername ? '=?utf-8?B?' . base64_encode($mats[1]) . "?= <$mats[2]>" : $mats[2]) : $toemail;
	;
	
	$email_subject='=?utf-8?B?' . base64_encode(preg_replace("/[\r|\n]/", '', '[' . $sitename . '] ' . $subject)) . '?=';
	$email_message=chunk_split(base64_encode(str_replace("\n", "\r\n", str_replace("\r", "\n", str_replace("\r\n", "\n", str_replace("\n\r", "\r", $message))))));
	
	$headers="From: $email_from{$maildelimiter}X-Priority: 3{$maildelimiter}X-Mailer: STWMS-V1 {$maildelimiter}MIME-Version: 1.0{$maildelimiter}Content-type: text/html; charset=utf-8{$maildelimiter}Content-Transfer-Encoding: base64{$maildelimiter}";
	
	if(!$fp=fsockopen($mail['server'], $mail['port'], $errno, $errstr, 30)){
		run_log('SMTP', $mail['server'] . '(:' . $mail['port'] . ') CONNECT - Unable to connect to the SMTP server', 0);
		return false;
	}
	stream_set_blocking($fp, true);
	
	$lastmessage=fgets($fp, 512);
	
	if(substr($lastmessage, 0, 3) != '220'){
		run_log('SMTP', $mail['server'] . ':' . $mail['port'] . ' CONNECT - ' . $lastmessage, 0);
		return false;
	}
	
	fputs($fp, ($mail['auth'] ? 'EHLO' : 'HELO') . " STWMS\r\n");
	$lastmessage=fgets($fp, 512);
	if(substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250){
		run_log('SMTP', '(' . $mail['server'] . ':' . $mail['port'] . ')' . ' HELO/EHLO - ' . $lastmessage, 0);
		return false;
	}
	
	while(1){
		if(substr($lastmessage, 3, 1) != '-' || empty($lastmessage)){
			break;
		}
		$lastmessage=fgets($fp, 512);
	}
	
	if($mail['auth']){
		fputs($fp, "AUTH LOGIN\r\n");
		$lastmessage=fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 334){
			run_log('SMTP', '(' . $mail['server'] . ':' . $mail['port'] . ')' . ' AUTH LOGIN - ' . $lastmessage, 0);
			return false;
		}
		
		fputs($fp, base64_encode($mail['user']) . "\r\n");
		$lastmessage=fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 334){
			run_log('SMTP', '(' . $mail['server'] . ':' . $mail['port'] . ')' . ' USERNAME - ' . $lastmessage, 0);
			return false;
		}
		
		fputs($fp, base64_encode($mail['password']) . "\r\n");
		$lastmessage=fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 235){
			run_log('SMTP', '(' . $mail['server'] . ':' . $mail['port'] . ')' . ' PASSWORD - ' . $lastmessage, 0);
			return false;
		}
		$email_from=$mail['from'];
	}
	
	fputs($fp, "MAIL FROM: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from) . ">\r\n");
	$lastmessage=fgets($fp, 512);
	if(substr($lastmessage, 0, 3) != 250){
		fputs($fp, "MAIL FROM: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from) . ">\r\n");
		$lastmessage=fgets($fp, 512);
		if(substr($lastmessage, 0, 3) != 250){
			run_log('SMTP', '(' . $mail['server'] . ':' . $mail['port'] . ')' . ' MAIL FROM - ' . $lastmessage, 0);
			return false;
		}
	}
	
	fputs($fp, "RCPT TO: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $toemail) . ">\r\n");
	$lastmessage=fgets($fp, 512);
	if(substr($lastmessage, 0, 3) != 250){
		fputs($fp, "RCPT TO: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $toemail) . ">\r\n");
		$lastmessage=fgets($fp, 512);
		run_log('SMTP', '(' . $mail['server'] . ':' . $mail['port'] . ')' . ' RCPT TO - ' . $lastmessage, 0);
		return false;
	}
	
	fputs($fp, "DATA\r\n");
	$lastmessage=fgets($fp, 512);
	if(substr($lastmessage, 0, 3) != 354){
		run_log('SMTP', '(' . $mail['server'] . ':' . $mail['port'] . ')' . ' DATA - ' . $lastmessage, 0);
		return false;
	}
	
	$headers.='Message-ID: <' . gmdate('YmdHs') . '.' . substr(md5($email_message . microtime()), 0, 6) . rand(100000, 999999) . '@' . $_SERVER['HTTP_HOST'] . ">{$maildelimiter}";
	
	fputs($fp, "Date: " . gmdate('r') . "\r\n");
	fputs($fp, "To: " . $email_to . "\r\n");
	fputs($fp, "Subject: " . $email_subject . "\r\n");
	fputs($fp, $headers . "\r\n");
	fputs($fp, "\r\n\r\n");
	fputs($fp, "$email_message\r\n.\r\n");
	$lastmessage=fgets($fp, 512);
	if(substr($lastmessage, 0, 3) != 250){
		run_log('SMTP', '(' . $mail['server'] . ':' . $mail['port'] . ')' . ' END - ' . $lastmessage, 0);
	}
	fputs($fp, "QUIT\r\n");
	return true;
}
?>