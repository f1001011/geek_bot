<?php
namespace app\common;
class CacheKey{
    const REDIS_TG_USER_INFO = 'redis_tg_user_info:%s';//用户登录发红红包系统，获取用户信息 token
    const REDIS_USER_INFO_TTL = 60*60*3;//用户登录发红红包系统，获取用户信息 token

    const REDIS_TG_USER = 'redis_tg_user:%s';// 用户tg登录过来的信息
    const REDIS_TG_USER_TTL = 60*100;
    const  REDIS_RED_ID_CREATE_SENG_INFO = 'bot_telegram:create_send_info:%s';//redis创建信息。
    const  REDIS_RED_ID_CREATE_SENG_INFO_TTL = 36000;

    //领取红包防止同意IP重复请求
    const REDIS_TELEGRAM_RED_POST_IP = 'bot_telegram:rob_red_repeat:%s';
    const REDIS_TELEGRAM_RED_POST_IP_TTL = 10;

    const REDIS_TELEGRAM_CROWD_TG_USER = 'bot_telegram:crowd_tg_user:%s';//保存用户最新的消息和用户所在的群组，用于用户发红包


    const REDIS_TELEGRAM_RED_SEND_POST = 'bot_telegram:rob_red_send_post:%s';
    const REDIS_TELEGRAM_RED_SEND_POST_TTL = 5;
    //用户领取红包之后发送出去的消息回执的消息ID
    const REDIS_TELEGRAM_RED_USER_MESSAGE_ID = 'bot_telegram:rob_red_message_id:%s';
    const REDIS_TELEGRAM_RED_END = 'bot_telegram:rob_red_end:%s';
    const REDIS_TELEGRAM_RED_RECEIVE_USER = 'bot_telegram:rob_red_receive_user:%s';
    const REDIS_TELEGRAM_RED_RECEIVE_USER_TTL = 60*60*5;
    const REDIS_TELEGRAM_RED_END_TTL = 36000;
    const REDIS_TELEGRAM_RED_USER_MESSAGE_ID_TTL = 36000;
    //当前红包信息最新的 回执消息  1个发起红包 对应一个ID
    const REDIS_TELEGRAM_RED_ID_AND_MESSAGE_ID = 'bot_telegram:rob_red_id_and_message_id:%s';
    const REDIS_TELEGRAM_RED_ID_AND_MESSAGE_ID_TTL = 36000;

    //红包发送时间。存红包发送的 time。如果是 有用户领取了，就换成最后领取时间
    const REDIS_RED_ID_START_SENG_DATE_JL = 'bot_telegram:red_start_send_date_jl';
    //接龙红包 任务 获取次数，如果超过 多少次，查询一次数据库，并写入新的数据
    const REDIS_RED_ID_START_SENG_DATE_JL_NUMBER = 'bot_telegram:red_start_send_date_jl_number';
    const REDIS_RED_ID_START_SENG_DATE_JL_TTL = 60*15;//红包领取信息保存15分钟

    //倒霉蛋列表
    const REDIS_RED_ID_HAPLESS_USER_LIST = 'bot_telegram:hapless:user_list';


    //计划任务执行状态，在执行 停止 消息队列，消息队列在执行，停止计划任务执行
    const REDIS_RED_COMMAND_IS_STATUS = 'bot_telegram:command_status:%s';



    const RED_AUTO_CLOSE = 'redautoclose';//计划任务key
    const RED_AUTO_SEND = 'redautosend';//计划任务key
    const RED_UNCLAIMED = 'redunclaimed';//计划任务key
    const RED_USER_MONEY_LOG = 'redusermoneylog';//计划任务key
    const SEL_HAPLESS_TASK = 'selhaplesstask';//计划任务key
    const TTL = 5*60;//计划任务key






}


