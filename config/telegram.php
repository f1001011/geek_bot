<?php

//$BOT_TOKEN = '7041131668:AAEkbJ0NBiJ461N9ri4s9OuBt3dXbC1dXm0';
$BOT_TOKEN = env('bot.bot_key', '6367289736:AAHbQutLFr0DlEa9Ct3wuOr8ebDLpB8q6Jw');
return [
    'crowd'=>'-4199654142',//机器人群号
    'bot-token'=>$BOT_TOKEN,
    'bot-url'     => "https://api.telegram.org/bot$BOT_TOKEN/",
    'bot-binding-url-one'=>"https://redapi.tggame.vip/api/bot/webhook",//红包领取  机器人要改过
    'bot-binding-url-two'=>"https://redapi.tggame.vip/api/bot/webhook",//发红包机器人
//    'bot-binding-active-url-one'=>'https://redapi.tggame.vip/',//我的绑定的网站页面地址https://plat-test.tggame.vip/#/redBag
    'bot-binding-active-url-one'=>'https://plat-test.tggame.vip/redBag',//我的绑定的网站页面地址
    'bot-binding-active-url-in-one'=>' https://t.me/red_app_test_bot/myRedTestName',//telegram 生成的 绑定对于页面的地址
    'bot-binding-red-string-one'=>'robRed_',
    'bot-binding-red-photo'=>'static/fl.jpg',//封面
    'bot-binding-red-photo-one'=>'static/fl.jpg',//福利
    'bot-binding-red-photo-two'=>'static/dx.jpg',//定向
    'bot-binding-red-photo-three'=>'static/jl.jpg',//接龙红包图
    'bot-binding-red-photo-four'=>'static/zl.jpg',//地雷
    'bot-binding-red-photo-ist'=>'static/jl.jpg',//龙头红包
    'bot-binding-red-service-charge'=>env('bot.bot_charge', 0.05),//红包服务费
    'bot-binding-red-zd-rate'=>2,//地雷红包倍数
    'bot-binding-red-no-appear'=>[0,1,2],//红雷红包最后位数 不会出现的名单
];
