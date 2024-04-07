<?php
## bin/redis-listener.php
#
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_time_limit(60);  // set a specific time - prevent to server crashes
ini_set('memory_limit', '1024M');

set_error_handler(function($errno, $errstr, $errfile, $errline ){
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});
$args = $_SERVER['argv'];
if (empty($args[1])) {
    die("Environment variable required");
}
if (! in_array($args[1], ['local', 'prod'])) {
    die("Environment variable no exists in available variables");
}
putenv("APP_ENV=$args[1]");
//
// WARNING !
// 
// config container must be declared after putenv("APP_ENV=$args[1]")
// functions.
//
require dirname(__DIR__)."/vendor/autoload.php";
$container = require dirname(__DIR__).'/config/container.php';
$config = $container->get('config');

use Predis\ClientInterface as Predis;
use Laminas\Mime\Part as MimePart;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
//
// Mailer
//
try {
    $predis = $container->get(Predis::class);
    $job = $predis->lpop('mailer');
    if (! empty($job)) {
        $params = json_decode($job, true);

        $isHtml = (bool)$params['isHtml'];
        $from = str_replace(['<','>'], '', $params['from']);
        $fromName = isset($params['fromName']) ? $params['fromName'] : null;
        $htmlBody = empty($params['body']) ? '' : (string)$params['body'];
        $subject = empty($params['subject']) ? '' : (string)$params['subject'];
        //
        // https://discourse.laminas.dev/t/zend-smtp-dkim-not-passing/1194

        // Setup SMTP transport
        // 
        $transport = new SmtpTransport();
        $options   = new SmtpOptions([
            'name' => 'olobase_default',
            'host' => $config['smtp']['host'],
            'port' => $config['smtp']['port'],
            'connection_class'  => 'login', // 'plain',
            'connection_config' => [
                'username' => $config['smtp']['username'],
                'password' => $config['smtp']['password'],
                'ssl'      => 'tls',
            ],
        ]);
        $transport->setOptions($options);

        // Build messsage

        $html = new MimePart($htmlBody);
        $html->charset = "UTF-8";
        if ($isHtml) { 
            $html->type = "text/html";
        } else {
            $html->type = "text/plain";
        }
        $body = new MimeMessage();
        $body->addPart($html);
        // $body->setParts(array($html));

        $message = new Message();
        $message->setEncoding('UTF-8');
        foreach ($params['to'] as $toEmailStr) {
            if (isset($params['name'][$toEmailStr])) {
                $message->addTo($toEmailStr, $params['name'][$toEmailStr]);
            } else {
                $message->addTo($toEmailStr);
            }
        }
        if (! empty($params['cc'])) {
            foreach ($params['cc'] as $ccEmailStr) {
                if (isset($params['name'][$ccEmailStr])) {
                    $message->addCc($ccEmailStr, $params['name'][$ccEmailStr]);
                } else {
                    $message->addCc($ccEmailStr);
                }
            }
        }
        if (! empty($params['bcc'])) {
            foreach ($params['bcc'] as $bccEmailStr) {
                if (isset($params['name'][$bccEmailStr])) {
                    $message->addBcc($bccEmailStr, $params['name'][$bccEmailStr]);
                } else {
                    $message->addBcc($bccEmailStr);
                }
            }    
        }
        $message->addFrom($from, $fromName);
        $message->setSubject($subject);
        $message->setBody($body);
        //
        // Send transport
        // 
        $transport->send($message);
    }
} catch (Exception $e) {
    $errorStr = $e->getMessage()." Error Line: ".$e->getLine();
    echo $errorStr.PHP_EOL;
    file_put_contents(PROJECT_ROOT."/data/tmp/error-output.txt", $errorStr.PHP_EOL, FILE_APPEND | LOCK_EX);
}