<?php
require_once "vendor/autoload.php";

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;


header('Content-type: text/html; charset=utf-8');

$rootPath = $_SERVER['DOCUMENT_ROOT'];

$config = json_decode(file_get_contents($rootPath . "/config.json"));
$settings = $config->settings;

#region Mailer setting
$mail = new PHPMailer;
$mail->CharSet = 'UTF-8';

// Настройки SMTP
$mail->SMTPDebug = SMTP::DEBUG_SERVER;
$mail->isSMTP();
$mail->SMTPAuth = true;
$mail->SMTPDebug = 0;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

$mail->Host = $settings->email->host;
$mail->Port = $settings->email->port;
$mail->Username = $settings->email->username;
$mail->Password = $settings->email->password;

// От кого
$mail->setFrom($settings->email->username);

// Кому
foreach ($settings->email->addressList as $address) {
    $mail->addAddress($address);
}

$mail->isHTML(true);
#endregion


#region Telegram setting
$botToken = $settings->telegram->botToken;
$botUsername = $settings->telegram->botUsername;
$chatId = $settings->telegram->chatId;
$telegram = new Telegram($botToken, $botUsername);
#endregion

$currentTime = time();

$objects = $config->objects;
//  Проверка объектов
foreach ($objects as $object) {
    $time = json_decode(file_get_contents($rootPath . "/storage/$object->id.json"))->time;

    // Если пошло 60 сек
    if ($time < $currentTime - 65 && $object->status !== 'error') {

        #region Send Telegram Message
        Request::sendMessage([
            'chat_id' => $chatId,
            'parse_mode' => 'html',
            "text" =>
                "‼️<b>Отключение интеренета на объекте $object->name</b> ‼️" .
                "\n\nДавно не было запросов с объекта.\nВозможно отключение интернета." .
                "\n\nПоследнее подключение: <b>" . date('d.m.Y H.i.s', $time) . "</b>"
        ]);
        #endregion

        #region Send gMail Message
        $mail->Subject = "Отключение интеренета на объекте $object->name";
        $body = '<p>Давно не было запросов с объекта. Возможно отключение интеренета/света</p>
                <p>Последнее подключение: <b>' . date('d.m.Y H.i.s', $time) . '</b></p>';
        $mail->msgHTML($body);
        $mail->send();
        #endregion

        $object->status = 'error';
        echo "Давно не было запросов у объекта $object->name в " . date('H:i:s', $time) . "\r\n";

    } else if ($time > $currentTime - 30 && $object->status === 'error') {
        #region Send Telegram Message
        Request::sendMessage([
            'chat_id' => $chatId,
            'parse_mode' => 'html',
            "text" =>
                "‼️<b>Подключение к $object->name востановлено</b> ‼️" .
                "\n\nПоследнее подключение: <b>" . date('d.m.Y H.i.s', $time) . "</b>"
        ]);
        #endregion

        #region Send gMail Message
        $mail->Subject = "Подключение к $object->name востановлено!";
        $body = '<p>Последнее подключение: <b>' . date('d.m.Y H.i.s', $time) . '</b></p>';
        $mail->msgHTML($body);
        $mail->send();
        #endregion

        $object->status = 'good';
    }
}

$sites = $config->sites;
//  Проверка сайта
foreach ($sites as $site) {
    $siteIsLoad = file_get_contents($site->url);
    //  Если сайт не загрузился
    if ($siteIsLoad === false && $site->status === "good") {
        $site->status = 'warning';
    } else if ($siteIsLoad === false && $site->status === 'warning') {
        #region Send Telegram Message
        Request::sendMessage([
            'chat_id' => $chatId,
            'parse_mode' => 'html',
            "text" =>
                "‼️<b>Перестал работать сайт: $site->name</b> ‼"
        ]);
        #endregion

        #region Send gMail Message
        $mail->Subject = "Перестал работать сайт: $site->name";
        $body = "<p>Проблема с подключением к сайту $site->name</p>";
        $mail->msgHTML($body);
        $mail->send();
        #endregion

        $site->status = 'error';
        echo "<b>Перестал работать сайт: $site->name</b>";
    } else if ($siteIsLoad !== false && $site->status === "error") {

        #region Send Telegram Message
        Request::sendMessage([
            'chat_id' => $chatId,
            'parse_mode' => 'html',
            "text" =>
                "‼️<b>Работа сайта $site->name востановлена</b> ‼️"
        ]);
        #endregion

        #region Send gMail Message
        $mail->Subject = "Работа сайта $site->name востановлена!";
        $body = "<p>Проблема с подключением к сайту $site->name отсутствует</p>";
        $mail->msgHTML($body);
        $mail->send();
        #endregion

        $site->status = 'good';
    }
}

file_put_contents($rootPath . "/config.json", json_encode([
    'settings' => $settings,
    'objects' => $objects,
    'sites' => $sites
]));

