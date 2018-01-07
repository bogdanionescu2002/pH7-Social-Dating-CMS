<?php
/**
 * @title            Mail Class
 * @desc             Mail Class derived from Swift Class
 *
 * @author           Pierre-Henry Soria <ph7software@gmail.com>
 * @copyright        (c) 2012-2017, Pierre-Henry Soria. All Rights Reserved.
 * @license          GNU General Public License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @package          PH7 / Framework / Mail
 * @version          1.2 (Last update 10/13/2015)
 */

namespace PH7\Framework\Mail;

defined('PH7') or exit('Restricted access');

use PH7\Framework\Mvc\Model\DbConfig;

use PH7\Framework\PHPMailer\PHPMailer;
  

class Mail
{
    /**
     * Send an email with Swift library engine.
     *
     * @param array $aInfo
     * @param string $sContents
     * @param boolean $bHtmlFormat
     *
     * @return integer Number of recipients who were accepted for delivery.
     */
    public function send(array $aInfo, $sContents, $bHtmlFormat = true)
    {

        // Default values
        $sFromMail = (empty($aInfo['from'])) ? DbConfig::getSetting('returnEmail') : $aInfo['from']; // Email noreply (generally noreply@yoursite.com)
        $sFromName = (empty($aInfo['form_name'])) ? DbConfig::getSetting('emailName') : $aInfo['form_name'];

        $sToMail = (empty($aInfo['to'])) ? DbConfig::getSetting('adminEmail') : $aInfo['to'];
        $sToName = (empty($aInfo['to_name'])) ? $sToMail : $aInfo['to_name'];

        $sSubject = $aInfo['subject'];

        // Setup the mailer
        $oTransport = \Swift_MailTransport::newInstance();
        $oMailer = \Swift_Mailer::newInstance($oTransport);
        $oMessage = \Swift_Message::newInstance()
            ->setSubject(escape($sSubject, true))
            ->setFrom(array(escape($sFromMail, true) => escape($sFromName, true)))
            ->setTo(array(escape($sToMail, true) => escape($sToName, true)));
        ($bHtmlFormat) ? $oMessage->addPart($sContents, 'text/html') : $oMessage->setBody($sContents);

        $iResult = $oMailer->send($oMessage);

        unset($oTransport, $oMailer, $oMessage);

        /*
         * Check if Swift is able to send message, otherwise we use the traditional native PHP mail() function
         * as on some hosts config, Swift Mail doesn't work.
         */
        if (!$iResult) {
            $aData = ['from' => $sFromMail, 'fromName' => $sFromName, 'to' => $sToMail, 'subject' => $sSubject, 'body' => $sContents];
            $iResult = (int)$this->phpMail($aData);
            //$iResult = (int)$this->trimite_mail($sToMail,'',$sSubject,$sContents,'');
        }
        
        //file_put_contents('../err.log', $iResult);
        //$aData = ['from' => $sFromMail, 'fromName' => $sFromName, 'to' => $sToMail, 'subject' => $sSubject, 'body' => $sContents];
        //$iResult = (int)$this->phpMail($aData);
        return $iResult;
    }

    /**
     * Send an email with the native PHP mail() function in text and HTML format.
     *
     * @param array $aParams The parameters information to send email.
     *
     * @return boolean Returns TRUE if the mail was successfully accepted for delivery, FALSE otherwise.
     */
    protected function phpMail(array $aParams)
    {
        // If the email sender is empty, we define the server email.
        if (empty($aParams['from'])) {
            $aParams['from'] = $_SERVER['SERVER_ADMIN'];
        }

        /*** Headers ***/
        // To avoid the email goes in the spam folder of email client.
        $sHeaders = "From: \"{$_SERVER['HTTP_HOST']}\" <{$_SERVER['SERVER_ADMIN']}>\r\n";

        $sHeaders .= "Reply-To: <{$aParams['from']}>\r\n";
        $sHeaders .= "MIME-Version: 1.0\r\n";
        $sHeaders .= "Content-Type: text/html; charset=\"utf-8\"\r\n";

        /** Send Email ***/
        //return @mail($aParams['to'], $aParams['subject'], $aParams['body'], $sHeaders);
        return $this->sendmail_PHPMailer($aParams['from'],$aParams['fromName'],$aParams['to'],'',$aParams['subject'],$aParams['body'],'');
    }
    
   
protected function sendmail_PHPMailer($from,$fromName,$to,$replyto,$subject,$body,$bodyalt) {

// SMTP data
$ssmtpHostName = DbConfig::getSetting('smtpHostName');
$ssmtpEmail = DbConfig::getSetting('returnEmail');
$ssmtpPassword = DbConfig::getSetting('smtpPassword');
$ssmtpPort = DbConfig::getSetting('smtpPort');
$sissmtpSSL = DbConfig::getSetting('issmtpSSL');
          
$mail = new PHPMailer;
    
//$mail->SMTPDebug = 3;                               // Enable verbose debug output
        
$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = $ssmtpHostName;  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = $ssmtpEmail;                  // SMTP username
$mail->Password = $ssmtpPassword;               // SMTP password

if ($sissmtpSSL == 1)
$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted

$mail->Port = $ssmtpPort;                                   // TCP port to connect to


$mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);

#if (empty($from)) {
#$from = 'noreply@matros.ro';
#$fromName = 'Noreply MateEros';
#}

$mail->setFrom($from, $fromName);

//$mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
$mail->addAddress($to);               // Name is optional

if (filter_var($replyto, FILTER_VALIDATE_EMAIL) && strlen(trim($replyto))>0) {
$mail->addReplyTo($replyto);
//$mail->addCC($replyto);
}

//$mail->addCC('cc@example.com');
//$mail->addBCC('admin@matros.ro');

//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
$mail->isHTML(true);                                  // Set email format to HTML

$mail->Subject = $subject;
$mail->Body    = $body;
$mail->AltBody = $bodyalt;


if(!$mail->send()) {
   // echo 'Message could not be sent.';
    //return 'Mailer Error: ' . $mail->ErrorInfo;
    return false; // $mail->ErrorInfo;
} else {
    return true;
} 



}    
    
    
}
