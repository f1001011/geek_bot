<?php

namespace app\command\service;

use app\model\LotteryJoinUserModel;
use app\model\MoneyLogModel;
use app\model\UserModel;
use think\facade\Db;

class RedUserMoneyLogService extends BaseService
{
    public function start(){
        //函数执行完。删除指定的key
        register_shutdown_function(function() {
            $this->delStatus('redusermoneylog');
        });

        //用户押金返还
        $list = [];//需要返回的押金列表，查询 红包领取表，status = 1 的 返回的金额就是 表中的
        $list = LotteryJoinUserModel::getInstance()->getDataList(['status' => 1]);
        if (empty($list)) {
            return;
        }

        $moneyLog = [];//写入用户的金额记录
        $updateJoinUser = [];//修改红包领取记录
        $updateUser = [];//更新用户余额


        foreach ($list as $key => $value) {
            if ($value['user_still_money'] > 0) {
                LotteryJoinUserModel::getInstance()->setUpdate(['id'=>$value['id']],['status'=>0]);//防止出问题
                continue;
            }

            $moneyLog[] = [
                'username' => $value['user_name'],
                'tg_id' => $value['tg_id'],
                'user_id' => $value['user_id'],
                'start_money' => 0,
                'end_money' => 0,
                'change_money' => $value['user_deposit_money'],
                'water_money' => 0,
                'source_id' => $value['lottery_id'],
                'to_source_id' => $value['id'],
                'remarks' => '返还用户押金:' . $value['user_deposit_money'],
                'type' => 3,//返用户押金
                'change_type' => $value['lottery_type'],
                'piping' => $value['crowd_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $updateJoinUser[] = [
                'id' => $value['id'],
                'user_still_money' => $value['user_deposit_money'],
                'status' => 0,
            ];

            if (isset($updateUser[$value['user_id']])) {
                $updateUser[$value['user_id']]['balance'] += $value['user_deposit_money'];
            } else {
                $updateUser[$value['user_id']] = [
                    'id' => $value['user_id'],
                    'balance' => $value['user_deposit_money'],
                ];
            }
        }


        //查询用户余额
        $userIdList = array_unique(array_column($list, 'user_id'));
        $userList = UserModel::getInstance()->whereIn('id', $userIdList)->field('id,balance,username,tg_id')->select()->toArray();
        $userList = array_column($userList, null, 'id');
        //本次结算返佣
        foreach ($updateUser as $key => $value) {
            if (isset($userList[$value['id']])) {
                $user = $userList[$value['id']];
                $moneyLog[] = [
                    'username' => $user['username'],
                    'tg_id' => $user['tg_id'],
                    'user_id' => $user['id'],
                    'start_money' => $user['balance'],
                    'end_money' => $user['balance'] + $value['balance'],
                    'change_money' => $value['balance'],
                    'water_money' => 0,
                    'source_id' => 0,
                    'to_source_id' => 0,
                    'remarks' => '任务总返回用户押金:' . $value['balance'],
                    'type' => 4,//返用户押金
                    'change_type' => 0,
                    'piping' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        }


        // 启动事务
        Db::startTrans();
        try {
            //遍历需要返还的佣金
            //写入日志
            MoneyLogModel::getInstance()->setInsertAll($moneyLog);
            //更新用户余额
            foreach ($updateUser as $k => $v) {
                UserModel::getInstance()->incOrDec($v['id'], $v['balance']);
            }
            //更新领取信息为全部结束
            LotteryJoinUserModel::getInstance()->updateAll($updateJoinUser);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }
}