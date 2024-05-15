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


//飞机路由回调 7041131668-AAEkbJ0NBiJ461N9ri4s9OuBt3dXbC1dXm0
//bot6367289736:AAHbQutLFr0DlEa9Ct3wuOr8ebDLpB8q6Jw
Route::any('/api/bot/webhook', ApiTelegramBotRedEnvelope::class.'/webhook');
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
Route::any('/auth', \app\controller\ApiTelegramBotRedSend::class.'/verifyUser');//用户登录，信息获取
Route::any('/send', \app\controller\ApiTelegramBotRedSend::class.'/send');//
Route::any('/set-create-send', \app\controller\ApiTelegramBotRedSend::class.'/userCreateSendBot')->middleware(\app\middleware\SignMiddleware::class);//用户创建  发起红包