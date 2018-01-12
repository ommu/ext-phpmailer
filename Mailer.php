<?php
/**
 * Mailer class file.
 *
 * @author Putra Sudaryanto <putra@sudaryanto.id>
 * @contact (+62)856-299-4114
 * @copyright Copyright (c) 2018 Ommu Platform (opensource.ommu.co)
 * @create date January 12, 2018 15:11 WIB
 * @link https://github.com/ommu/ommu-core
 *
 */

$vendor_path = Yii::getPathOfAlias('application.vendor');
require_once($vendor_path.'/autoload.php'); // register Yii autoloader

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
	/**
	 * Sent Email
	 */
	public static function sendEmail($to_email=null, $to_name=null, $subject, $message, $cc=null, $attachment=null, $passing=true, $edebug=0) 
	{
		ini_set('max_execution_time', 0);
		ob_start();
		
		$model = SupportMailSetting::model()->findByPk(1,array(
			'select' => 'mail_contact, mail_name, mail_from, mail_smtp, smtp_address, smtp_port, smtp_username, smtp_password, smtp_ssl',
		));
		$setting = OmmuSettings::model()->findByPk(1, array(
			'select' => 'site_title',
		));
		
		$debugMail = Yii::app()->params['debug']['send_email'];
		$debugStatus = $debugMail['status'];
		$debugContent = $debugMail['content'];
		$debugEmail = $debugMail['email'];
		
		if ($debugStatus && $debugContent == 'file_put_contents' && in_array(Yii::app()->request->serverName, array('localhost','127.0.0.1','192.168.3.13'))) {
			file_put_contents(Utility::getUrlTitle($subject).'.htm', $message);
			
		} else {
			if ($debugStatus && $debugContent == 'send_email' && in_array(Yii::app()->request->serverName, array('localhost','127.0.0.1','192.168.3.13')))
				$to_email = $to_name = $debugEmail;
				
			$mail = new PHPMailer($passing);										// Passing `true` enables exceptions
			try {
				//Server settings
				$mail->SMTPDebug = $edebug;											// Enable verbose debug output
				$mail->isSMTP();													// Set mailer to use SMTP
				$mail->Host = $model->smtp_address;									// Specify main and backup SMTP servers
				$mail->SMTPAuth = true;												// Enable SMTP authentication
				$mail->Username = $model->smtp_username;							// SMTP username
				$mail->Password = $model->smtp_password;							// SMTP password
				$mail->Port = $model->smtp_port;									// TCP port to connect to
				if ($model->smtp_ssl != 0)
					$mail->SMTPSecure	= $model->smtp_ssl == 1 ? "tls" : "ssl";	// Enable TLS encryption, `ssl` also accepted
			
				//Recipients
				if($to_email != null && $to_name != null) {
					$mail->setFrom($model->mail_from, $model->mail_name);
					$mail->addReplyTo($model->mail_from, $model->mail_name);
					if ($to_email == $to_email)
						$mail->addAddress($to_email);								// Name is optional
					else
						$mail->addAddress($to_email, $to_name);						// Add a recipient
				} else {
					$mail->setFrom($model->mail_from, $model->mail_name);
					$mail->addReplyTo($model->mail_from, $model->mail_name);
					if ($to_email == $to_name)
						$mail->addAddress($to_email);								// Name is optional
					else
						$mail->addAddress($to_email, $to_name);						// Add a recipient
				}
				
				//CC
				if ($cc) {
					if (is_array($cc) && !empty($cc)) {
						foreach ($cc as $name => $email) {
							$mail->addCC($email);
							//$mail->addCC($email, $name);
						}
					} else
						$mail->addCC($cc);
					//$mail->addBCC('bcc@example.com');
				}
			
				//Attachments
				if ($attachment) {
					if (is_array($attachment) && !empty($attachment)) {
						foreach ($attachment as $name => $file) {
							$mail->addAttachment($file);						// Add attachments
							//$mail->addAttachment($file, $name);				// Optional name
						}
					} else
						$mail->addAttachment($file);							// Add attachments
				}
			
				//Content
				$mail->isHTML(true);											// Set email format to HTML
				$mail->Subject = $subject;
				$mail->Body    = $message;
				//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
			

				if($mail->send()) {
					return true;
					//echo 'Message has been sent';
				} else {
					return false;
					//echo 'Message could not be sent.';
				}

			} catch (Exception $e) {
				echo 'Message could not be sent.';
				echo 'Mailer Error: ' . $mail->ErrorInfo;
			}
		}

		ob_end_flush();
	}
}
