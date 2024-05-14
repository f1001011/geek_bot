<?php

//$BOT_TOKEN = '7041131668:AAEkbJ0NBiJ461N9ri4s9OuBt3dXbC1dXm0';
$BOT_TOKEN = env('bot.bot_key', '7041131668:AAEkbJ0NBiJ461N9ri4s9OuBt3dXbC1dXm0');
return [
    'bot-token'=>$BOT_TOKEN,
    'bot-url'     => "https://api.telegram.org/bot$BOT_TOKEN/",
    'bot-binding-url-one'=>"https://redapi.tggame.vip/api/bot$BOT_TOKEN/webhook",//红包领取
    'bot-binding-url-two'=>"https://redapi.tggame.vip/api/bot$BOT_TOKEN/webhook",//发红包机器人
    'bot-binding-active-url-one'=>'https://redapi.tggame.vip/',//绑定的网站页面地址
    'bot-binding-red-string-one'=>'robRed_',
    'bot-binding-red-photo-one'=>'static/fl.jpg',//福利
    'bot-binding-red-photo-two'=>'static/dx.jpg',//定向
    'bot-binding-red-photo-three'=>'static/jl.jpg',//接龙红包图
    'bot-binding-red-photo-four'=>'static/zl.jpg',//地雷
    'bot-binding-red-photo-ist'=>'static/jl.jpg',//龙头红包
    'bot-binding-red-service-charge'=>env('bot.bot_charge', 0.05),//红包服务费
];