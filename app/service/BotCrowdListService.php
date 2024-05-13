<?php

namespace app\service;

use app\model\BotCrowdListModel;

class BotCrowdListService extends BaseService
{

    //机器人加入到新房间

    public function botCrowdBind($data = [])
    {
        $res = BotCrowdListModel::getInstance()->getDataOne(['crowd_id' => $data['crowd_id'], 'botname' => $data]);
        if (empty($res)) {
            BotCrowdListModel::getInstance()->setInsert($data);
        } else {
            BotCrowdListModel::getInstance()->setUpdate(['crowd_id' => $data['crowd_id'], 'botname' => $data], $data);
        }
        return true;
    }

    public function botCrowdEdit($data)
    {
        BotCrowdListModel::getInstance()->setUpdate(['crowd_id' => $data['crowd_id'], 'botname' => $data], ['del'=>1]);
        return true;
    }
}