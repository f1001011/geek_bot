<?php

namespace app\service\log;

use app\model\LotteryJoinModel;
use app\model\LotteryJoinUserModel;
use app\service\BaseService;

class MoneyLog extends BaseService
{
    public function joinUserLog($tgId,$page,$limit)
    {
        //获取用户参与红包记录
        $page = LotteryJoinUserModel::getInstance()->getDataPageList(['tg_id' => $tgId], ['id' => 'desc'], $page, $limit);
        return $page;
    }

    //获取发送列表
    public function joinLog($tgId,$page,$limit)
    {
        $page = LotteryJoinModel::getInstance()->getDataPageList(['tg_id' => $tgId], ['id' => 'desc'], $page, $limit);
        return $page;
    }
}