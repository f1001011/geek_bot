<?php
use think\facade\Route;
use \app\controller\ApiTelegramBotRedEnvelope;
Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});
Route::any('/', \app\controller\Index::class.'/index');
//绑定地址
Route::any('/set-webhook', ApiTelegramBotRedEnvelope::class.'/setWebhook');
Route::any('/set-delete', ApiTelegramBotRedEnvelope::class.'/setDelete');

//开始发送红包
Route::any('/send-start-bot', ApiTelegramBotRedEnvelope::class.'/sendStartBot');


//飞机路由回调
Route::any('/api/7041131668-AAEkbJ0NBiJ461N9ri4s9OuBt3dXbC1dXm0/webhook', ApiTelegramBotRedEnvelope::class.'/webhook');
Route::any('/get-webhook-info', ApiTelegramBotRedEnvelope::class.'/getWebhookInfo');
Route::any('/get-chats', ApiTelegramBotRedEnvelope::class.'/getChats');

//后台创建发红包订单
Route::any('/set-create-send-bot', ApiTelegramBotRedEnvelope::class.'/createSendBot');
//点击发送按钮，发送红包订单
Route::any('/set-send-bot', ApiTelegramBotRedEnvelope::class.'/setSendBot');


//下面为测试接口
Route::any('/test', ApiTelegramBotRedEnvelope::class.'/test');

Route::any('/botbot', ApiTelegramBotRedEnvelope::class.'/botbot');


###########################################################
//红包发送机器人 7142894277:AAFFkwmT8Y29sTzMB_PgC5v4sNVRQgSLYGw
Route::any('/api/7142894277-AAFFkwmT8Y29sTzMB_PgC5v4sNVRQgSLYGw/webhook', ApiTelegramBotRedEnvelope::class.'/webhook');