<?php

namespace app\facade\bin;


class TelegramBotBin extends BaseFacade
{
    private static $url;

    public function __construct($u = '')
    {
        self::$url = empty($u) ? config('telegram.bot-url') : $u;
    }

    public static function stores($url)
    {
        return new self($url);
    }

    //绑定域名
    public static function setWebhookPost($str = 'one')
    {
        $url = self::$url . 'setWebhook?url=' . config("telegram.bot-binding-url-{$str}");
        $response = curlPost($url);
        return $response;
    }

    public static function setWebhookDeletePost()
    {
        $url = self::$url . 'deleteWebhook';
        $response = curlPost($url);
        return $response;
    }

    public function getChats()
    {
        $url = self::$url . 'getChats';
        $response = curlPost($url);
        return $response;
    }

    //获取绑定信息
    public static function getWebhookPostInfo()
    {
        $url = self::$url . 'getWebhookInfo';
        $response = curlPost($url);
        return $response;
    }

    //发送图片文件
    public static function sendPhoto($chatId, $photoPath, $caption = '你好欢迎来使用机器人', $keyboard = [])
    {
        // 创建一个 cURL 句柄
        $ch = curl_init();
        // 设置请求的 URL
        $url = self::$url . 'sendPhoto';

        // 创建一个包含要发送的字段的数组
        $postFields = [
            'chat_id' => $chatId,
            'photo' => new \CURLFile($photoPath), // 使用 CURLFile 发送本地文件
            'parse_mode' => 'HTML'
        ];

        if (!empty($caption)) {
            $postFields['caption'] = $caption;
        }
        // 将键盘编码为JSON
        if (!empty($keyboard)) {
            $postFields['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        // 设置 cURL 选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1); // 发送 POST 请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields); // 设置 POST 字段
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 将返回结果保存到变量中，而不是直接输出
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true); // 在 PHP 5.6.0+ 中启用安全文件上传

        // 发送请求并获取响应
        $response = curl_exec($ch);
        // 检查是否有错误发生
        if (curl_errno($ch)) {
            traceLog(curl_error($ch), 'sendPhoto error');
        }

        // 关闭 cURL 句柄
        curl_close($ch);
        // 输出响应结果
        traceLog($response, 'sendPhoto');

        return $response;
    }

    public static function sendPhotoEdit($chatId, $photoPath, $caption = '你好欢迎来使用机器人', $keyboard = [], $messageId = 0)
    {
        // 删除旧消息
        if ($messageId > 0) {
            $deleteUrl = self::$url . "deleteMessage";
            $deleteData = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'parse_mode' => 'HTML'
            ];

            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($deleteData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $deleteResult = curl_exec($ch);
            curl_close($ch);
        }

        // 创建一个 cURL 句柄
        $ch = curl_init();
        // 设置请求的 URL
        $url = config('telegram.bot-url') . 'sendPhoto';

        // 创建一个包含要发送的字段的数组
        $postFields = [
            'chat_id' => $chatId,
            'photo' => new \CURLFile($photoPath), // 使用 CURLFile 发送本地文件
        ];

        if (!empty($caption)) {
            $postFields['caption'] = $caption;
        }
        // 将键盘编码为JSON
        if (!empty($keyboard)) {
            $postFields['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        // 设置 cURL 选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1); // 发送 POST 请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields); // 设置 POST 字段
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 将返回结果保存到变量中，而不是直接输出
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true); // 在 PHP 5.6.0+ 中启用安全文件上传

        // 发送请求并获取响应
        $response = curl_exec($ch);
        // 检查是否有错误发生
        if (curl_errno($ch)) {
            traceLog(curl_error($ch), 'sendPhoto error');
        }
        // 关闭 cURL 句柄
        curl_close($ch);
        // 输出响应结果
        traceLog($response, 'sendPhoto');
        return $response;
    }

    public static function sendWebhookEdit($chatId, $messageId, $message)
    {
        // 构造请求的 URL
        $url = self::$url . 'editMessageText?' . "chat_id={$chatId}&message_id={$messageId}&text={$message}";
        // 初始化 cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 执行请求并获取响应
        $response = curl_exec($ch);
        // 检查是否有错误发生
        if (curl_errno($ch)) {
            traceLog(curl_error($ch), 'sendWebhookEdit error');
        }
        // 关闭 cURL 资源
        curl_close($ch);
        // 处理响应（如果需要）
        traceLog($response, 'sendWebhookEdit');
        return $response;
    }

    /**
     */
    public static function sendWebhook($chatId = '', $message = '', $keyboard = [])
    {


        // 初始化 cURL
        $url = self::$url . 'sendMessage';
        // 将键盘编码为JSON
        // 准备 POST 数据
        $postData = array(
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        );

        if (!empty($keyboard)) {
            $postData['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }
        // 初始化 cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 发送请求并获取响应
        $response = curl_exec($ch);
        // 检查是否有错误发生
        if ($response === false) {
            traceLog(curl_error($ch), 'sendMessage error');
        }

        // 关闭 cURL 资源
        curl_close($ch);
        traceLog($response, 'sendMessage');
        // 处理响应（如果需要）
        return $response;
    }

    //发送点击内联菜单的弹出框  $callbackQueryId 回调ID  $message 消息内容
    public static function SendCallbackQuery($callbackQueryId, $message)
    {
        // 发送一个answerCallbackQuery响应来确认查询
        $answerCallbackQuery = [
            'callback_query_id' => $callbackQueryId,
            'text' => $message, // 这将作为Telegram客户端的提示显示（如果'show_alert'设置为true）
            'show_alert' => true, // 设置为true将在Telegram客户端中显示一个提示
            'parse_mode' => 'HTML'
        ];
        $url = config('Telegram.bot-url') . "answerCallbackQuery";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($answerCallbackQuery));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);

        if (curl_errno($ch)) {
            traceLog(curl_error($ch), 'sendWebhookEdit error');
        }
        // 关闭 cURL 资源
        curl_close($ch);
        traceLog($response, 'sendWebhookEdit');
        // 处理响应（如果需要）
        return $response;
    }

    //修改内联键盘按钮
    public static function editMessageReplyMarkup($chatId, $messageId, $keyboard)
    {
        $encodedKeyboard = [];
        if (!empty($keyboard)) {
            $encodedKeyboard = json_encode(['inline_keyboard' => $keyboard]);
        }

        // 发送一个空消息，只包含内联键盘
        $editMessageUrl = config('Telegram.bot-url') . "editMessageReplyMarkup";
        $postFields = [
            'chat_id' => $chatId,
            'message_id' => $messageId, // 注意：这里应该是新的message_id，因为你将发送一个新消息
            'reply_markup' => $encodedKeyboard,
            'parse_mode' => 'HTML'
        ];
        $ch = curl_init($editMessageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            traceLog(curl_error($ch), 'editMessageReplyMarkup error');
        }
        curl_close($ch);
        traceLog($response, 'editMessageReplyMarkup');
        // 处理响应（如果需要）
        return $response;
    }

    //修改发送图片消息和内联键盘
    public static function editMessageCaption($chatId = 0, $messageId = 0, $message = '', $keyboard = '')
    {
        // 发送一个空消息，只包含内联键盘
        $editMessageUrl = self::$url . "editMessageCaption";
        $postFields = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $message,
            'parse_mode' => 'HTML'
        ];
        if (!empty($keyboard)) {
            $postFields['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        $ch = curl_init($editMessageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            traceLog(curl_error($ch), 'editMessageCaption error');
        }
        curl_close($ch);
        traceLog($response, 'editMessageCaption');
        // 处理响应（如果需要）
        return $response;
    }

    public static function editMessageText($chatId = 0, $messageId = 0, $message = '', $keyboard = '')
    {
        // 发送一个空消息，只包含内联键盘
        $editMessageUrl = self::$url . "editMessageText";
        $postFields = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        if (!empty($keyboard)) {
            $postFields['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        $ch = curl_init($editMessageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            traceLog(curl_error($ch), 'editMessageText error');
        }
        curl_close($ch);
        traceLog($response, 'editMessageText');
        // 处理响应（如果需要）
        return $response;
    }
}
