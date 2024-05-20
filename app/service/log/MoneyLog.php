<?php

namespace app\service\log;

use app\model\LotteryJoinModel;
use app\model\LotteryJoinUserModel;
use app\model\MoneyLogModel;
use app\model\UserModel;
use app\service\BaseService;

class MoneyLog extends BaseService
{
    public function joinUserLog($tgId, $page, $limit)
    {
        //获取用户参与红包记录
        $page = LotteryJoinUserModel::getInstance()->getDataPageList(['tg_id' => $tgId], ['id' => 'desc'], $page, $limit);
        return $page;
    }

    //获取发送列表
    public function joinLog($tgId, $page, $limit)
    {
        $page = LotteryJoinModel::getInstance()->getDataPageList(['tg_id' => $tgId], ['id' => 'desc'], $page, $limit);
        return $page;
    }

    //查询用户余额
    public function balanceInquiry($tgId)
    {
        //查询用户余额
       return UserModel::getInstance()->getDataOne(['tg_id'=>$tgId]);
    }

    //查询用户余额
    public function balanceLog($tgId, $page, $limit)
    {
        //查询用户余额
        return MoneyLogModel::getInstance()->getDataPageList(['tg_id' => $tgId], ['id' => 'desc'], $page, $limit);
    }
}