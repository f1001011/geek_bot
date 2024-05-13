<?php

namespace app\command\service;

use app\model\LotteryJoinModel;
use app\model\LotteryJoinUserModel;

class RedAutoCloseService extends BaseService
{

    public function start(){
        //函数执行完。删除指定的key
        register_shutdown_function(function() {
            $this->delStatus('redautoclose');
        });

        //轮询没有结束的红包 //, 'is_status' => LotteryJoinModel::IS_STATUS_START_JL
        $getDataList = LotteryJoinModel::getInstance()->getDataList(['status' => LotteryJoinModel::STATUS_START]);
        if (empty($getDataList)) {
            return true;
        }
        //遍历数据。修改状态
        $updateDataList = [];
        //需要修改状态的数据
        foreach ($getDataList as $key => $value) {
            $update = [];
            //$update = $this->dx($value);
//            //判断红包类型 定向或者福利红包
            if ($value['lottery_type'] == LotteryJoinModel::RED_TYPE_FL || $value['lottery_type'] == LotteryJoinModel::RED_TYPE_DX || $value['lottery_type'] == LotteryJoinModel::RED_TYPE_DL) {
                $update = $this->dx($value);
            }
            if ($value['lottery_type'] == LotteryJoinModel::RED_TYPE_JL) {
                $update = $this->jl($value);
            }
            if (!empty($update)) {
                $updateDataList[] = $update;
            }
        }

        LotteryJoinModel::getInstance()->updateAll($updateDataList);
    }

    protected function dx($value)
    {
        $update = [];
        //1 判断是否需要更改领取状态
        if ($value['join_num'] <= $value['to_join_num']) {
            //全部领取完的红包
            $update = [
                'id' => $value['id'],
                'status' => LotteryJoinModel::STATUS_END_ALL,
                'is_status' => LotteryJoinModel::IS__STATUS_END_JL
            ];
        } elseif ($value['money'] <= $value['to_money']) {
            $update = [
                'id' => $value['id'],
                'status' => LotteryJoinModel::STATUS_END_ALL,
                'is_status' => LotteryJoinModel::IS__STATUS_END_JL
            ];
        } elseif (time() > (strtotime($value['start_at']) + $value['expire_at']) && $value['expire_at'] != 0) {
            $update = [
                'id' => $value['id'],
                'status' => LotteryJoinModel::STATUS_END,
                'is_status' => LotteryJoinModel::IS__STATUS_END_JL
            ];
        }

        //结束的红包。没领取完返还用户
        return $update;
    }

    //确保接龙红包 没领取完的，并且时间到了的，给结束掉
    protected function jl($value)
    {
        $update = [];
        if (time() > (strtotime($value['start_at']) + $value['expire_at']) && $value['expire_at'] != 0) {
            $update = [
                'id' => $value['id'],
                'status' => LotteryJoinModel::STATUS_END,
            ];
            //已经接龙过的更改状态
//            if (empty($value['jl_pid_id'])){
//                $update['is_status'] = LotteryJoinModel::IS__STATUS_END_JL;
//            }
            //用户领取红包人数不足的 需要结束
            if ($value['to_join_num'] < $value['join_num']) {
                $update['is_status'] = LotteryJoinModel::IS__STATUS_END_JL;
                //还需要更改 用户返还金额状态
                LotteryJoinUserModel::getInstance()->setUpdate(['lottery_id' => $value['id']], ['status' => 1]);
            }

        } elseif ($value['join_num'] <= $value['to_join_num']) {//领取完了
            $update = [
                'id' => $value['id'],
                'status' => LotteryJoinModel::STATUS_END,
            ];

        } elseif ($value['money'] <= $value['to_money']) {//领取完了
            $update = [
                'id' => $value['id'],
                'status' => LotteryJoinModel::STATUS_END,
            ];
        }
        return $update;
    }
}