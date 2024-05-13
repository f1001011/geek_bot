<?php

namespace app\command\service;

use app\model\LotteryJoinModel;
use app\service\BotJieLongRedEnvelopeService;
use app\service\BotRedEnvelopeService;

class RedAutoSendService extends BaseService
{

    public function start(){
        //函数执行完。删除指定的key
        register_shutdown_function(function() {
            $this->delStatus('redautosend');
        });

        //查询是否有没发送出去的红包
        $list = LotteryJoinModel::getInstance()->getCacheCreateInfoList();

        if (empty($list)){
            return false;
        }

        //操作发送
        foreach ($list as $key=>$value){
            if ($value['lottery_type'] == LotteryJoinModel::RED_TYPE_FL || $value['lottery_type'] == LotteryJoinModel::RED_TYPE_DX){
                BotRedEnvelopeService::getInstance()->setSend($value['id']);
            }

            if ($value['lottery_type'] == LotteryJoinModel::RED_TYPE_JL){
                BotJieLongRedEnvelopeService::getInstance()->setSend($value['id']);
            }
        }

    }
}