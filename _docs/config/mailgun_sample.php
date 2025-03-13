<?php
// config/mailgun.php
// Mailgun API configuration

define('MAILGUN_API_KEY', 'your-mailgun-api-key');
define('MAILGUN_DOMAIN', 'your-mailgun-domain.com');
define('MAILGUN_FROM_EMAIL', 'noreply@yourdomain.com');
define('MAILGUN_FROM_NAME', 'Bug Tracker');

/**
 * Send email using Mailgun API
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $html HTML content
 * @param string $text Plain text content
 * @return bool Success status
 */
function sendEmail($to, $subject, $html, $text = '') {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/' . MAILGUN_DOMAIN . '/messages');
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:' . MAILGUN_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, true); 
    
    $postData = [
        'from' => MAILGUN_FROM_NAME . ' <' . MAILGUN_FROM_EMAIL . '>',
        'to' => $to,
        'subject' => $subject,
        'html' => $html
    ];
    
    if (!empty($text)) {
        $postData['text'] = $text;
    }
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    return ($httpCode == 200);
}
?>