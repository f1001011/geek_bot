<?php

namespace app\command\service;

use app\model\LotteryJoinModel;
use app\model\MoneyLogModel;
use app\model\UserModel;
use think\facade\Db;

class RedUnclaimedService extends BaseService
{
    public function start(){
        //函数执行完。删除指定的key
        register_shutdown_function(function() {
            $this->delStatus('redunclaimed');
        });

        //用户没领取完的红包，金额返回
        $list = LotteryJoinModel::getInstance()->getDataList(['status' => 2, 'is_status' => 2]);
        if (empty($list)) {
            return true;
        }

        //返回没领取完的红包
        $userUpdate = [];//用户 每有一笔红包就 写一笔，为了下面遍历 生成用户 金额日志  用户ID没去重
        $joinUpdateAll = [];//需要修改的 红包状态列表
        foreach ($list as $key => $value) {
            $joinUpdate = ['id'=>$value['id'],'status'=>LotteryJoinModel::STATUS_END_ALL];
            if ($value['money'] - $value['to_money'] <= 0){
                //全部领取完了的
            }else{
                //没领取完 需要返回用户的余额的
                $money = $value['money'] - $value['to_money']; //需要换给用户的金额
                $userUpdate[] = ['balance' => $money, 'id' => $value['user_id'], 'lottery_id' => $value['id'], 'change_type' => $value['lottery_type'], 'type' => 5, 'crowd' =>  $value['crowd'],];
                $joinUpdate['is_status'] = LotteryJoinModel::IS__STATUS_END_JL;
            }
            $joinUpdateAll[]  =$joinUpdate;
        }



        //查询用户余额
        $userIdList = array_unique(array_column($list, 'user_id'));
        $userList = UserModel::getInstance()->whereIn('id', $userIdList)->field('id,balance,username,tg_id')->select()->toArray();
        $userList = array_column($userList, null, 'id');

        $moneyLog = [];//资金记录
        $userUpdateAll = [];//实际需要修改金额的用户  id去重了
        foreach ($userUpdate as $key => $value) {
            if (!isset($userList[$value['id']])) {
                continue;
            }
            if (isset($userUpdateAll[$value['id']])){
                $userUpdateAll[$value['id']]['balance'] += $value['balance'];
            }else{
                $userUpdateAll[$value['id']] = ['balance' => $value['balance'], 'id' => $value['id']];
            }

            $user = $userList[$value['id']];
            $moneyLog[] = [
                'username' => $user['username'],
                'tg_id' => $user['tg_id'],
                'user_id' => $user['id'],
                'start_money' => $user['balance'],
                'end_money' => $user['balance'] + $value['balance'],
                'change_money' => $value['balance'],
                'water_money' => 0,
                'source_id' => $value['lottery_id'],
                'to_source_id' => 0,
                'remarks' => '本次任务总返回用户未领取红包金额:' . $value['balance'],
                'type' => $value['type'],//返用户押金
                'change_type' => $value['change_type'],
                'piping' => $value['crowd'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $userList[$value['id']]['balance'] = $user['balance'] + $value['balance'];
            //$userList[$value['id']]['balance'] = $user['balance'] + $value['balance'];
        }


        // 启动事务
        Db::startTrans();
        try {
            //修改用户余额
            foreach ($userUpdateAll as $k => $v) {
                UserModel::getInstance()->incOrDec($v['id'], $v['balance']);
            }
            //修改需要更新的红包列表
            LotteryJoinModel::getInstance()->updateAll($joinUpdateAll);
            MoneyLogModel::getInstance()->setInsertAll($moneyLog);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }


    }
}