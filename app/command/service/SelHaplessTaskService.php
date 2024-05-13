<?php

namespace app\command\service;

use app\model\LotteryJoinModel;
use app\model\LotteryJoinUserModel;
use app\model\UserModel;
use app\service\BotJieLongRedEnvelopeService;

class SelHaplessTaskService extends BaseService
{

    public function start(){
        //函数执行完。删除指定的key
        register_shutdown_function(function() {
            $this->delStatus('redusermoneylog');
        });

        //1 查询 redis 是否最近有发红包信息
        $jlTime = BotJieLongRedEnvelopeService::getInstance()->botRedStartSendOrUserEndData(true);
        //如果短时间没有，就直接结束任务
        if (!$jlTime) {
            return;
        }

        //2 如果长时间没有，就查询数据库确认数据
        $getDataList = LotteryJoinModel::getInstance()->getDataList([
            'status' => LotteryJoinModel::STATUS_END,
            'lottery_type' => LotteryJoinModel::RED_TYPE_JL,
            //'hapless_user_id'=>0,
            'is_status' => LotteryJoinModel::IS_STATUS_START_JL
        ]);

        //判断是否有接龙红包
        if (empty($getDataList)) {
            return;
        }

        $list = array_slice($getDataList, 0, 100);//一次最多处理20条

        //获取倒霉蛋列表 和 红包数据需要跟新的  //6 满足条件了。开始选取倒霉蛋
        //$haplessUserList 倒霉蛋列表  $ListUpdate 所有的需要跟新的数据，有倒霉蛋的   $setListUpdate 直接更新的数据，没有倒霉蛋的
        list($haplessUserList, $ListUpdate, $setListUpdate) = $this->getHaplessUserList($list);
        //7 选取完成，公布开奖信息，公布倒霉蛋， 倒霉蛋自动开始发送红包
        //倒霉蛋开始发送红包
        //没有倒霉蛋，说明都需要跟新数据
//        if (empty($haplessUserList)) {
//            if (!empty($ListUpdate)) {
//                LotteryJoinModel::getInstance()->updateAll($ListUpdate);
//                $output->writeln('selhaplesstask 没有倒霉蛋，跟新数据');
//                return;
//            }
//            $output->writeln('selhaplesstask 没有倒霉蛋，没有更新的数据');
//            return;
//        }
        //没有倒霉蛋的直接更新
        if (!empty($setListUpdate)) {
            LotteryJoinModel::getInstance()->updateAll($ListUpdate);
        }
        if (empty($haplessUserList)){
            //LotteryJoinModel::getInstance()->updateAll($ListUpdate);
            $output->writeln('selhaplesstask 没有倒霉蛋，跟新数据');
        }

        //有倒霉蛋
        foreach ($ListUpdate as $key => $value) {
            //倒霉蛋开始发送红包
            if (isset($haplessUserList[$value['id']]) && $value['id'] == $haplessUserList[$value['id']]['lottery_id']) {

                $haplessLotteryJoinUser = $haplessUserList[$value['id']];//倒霉蛋的领奖信息
                //发红包
                $insert = [];//插入红包信息
                $lotteryJoinUserUpdate = [];//红包领取信息更改
                $lotteryJoinUpdate = [];//需要更改的红包信息
                $value['jl_number'] = $value['jl_number']+1;


                //需要更新的红包信息
                $lotteryJoinUpdate = [
                    'id' => $value['id'],
                    'is_status' => LotteryJoinModel::IS__STATUS_END_JL,
                    'hapless_user_id' => $haplessLotteryJoinUser['user_id']
                ];

                //红包领取信息更改
                $lotteryJoinUserUpdate = [
                    'id' => $haplessLotteryJoinUser['id'],
                    'is_hapless' => 1,
                ];

                //获取用户信息
                $userInfo = UserModel::getInstance()->getDataOne(['id'=>$haplessLotteryJoinUser['user_id']]);

                $insert = [
                    'tg_id' => $haplessLotteryJoinUser['tg_id'],
                    'username' => $userInfo['username'],
                    'crowd' => $value['crowd'],
                    'user_id' => $haplessLotteryJoinUser['user_id'],
                    'status' => LotteryJoinModel::STATUS_HAVE,
                    'activity_on' => getRedEnvelopeOn(20, "-{$value['jl_number']}"),
                    'money' => $value['money'],
                    'water_money' => $value['water_money'],
                    'join_num' => $value['join_num'],
                    'to_join_num' => 0,
                    'expire_at' => $value['expire_at'],
                    'start_at' => date('Y-m-d H:i:s'),
                    'in_join_user' => '',
                    'lottery_type' => LotteryJoinModel::RED_TYPE_JL,
                    'user_start_money' => $haplessLotteryJoinUser['user_start_money'],
                    'user_end_money' => $haplessLotteryJoinUser['user_end_money'],
                    'jl_number' => $value['jl_number'],
                    'jl_pid_id' => empty($value['jl_pid_id']) ? $value['id'] : $value['jl_pid_id'] . ',' . $value['id'],
                ];

                //资金记录
                $moneyLog = [
                    'username' => $userInfo['username'],
                    'tg_id' => $userInfo['tg_id'],
                    'user_id' => $userInfo['id'],
                    'start_money' => $userInfo['balance'],
                    'end_money' => $userInfo['balance'],
                    'change_money' => 0,
                    'water_money' => 0,
                    'to_source_id' => 0,
                    'remarks' => '用户创建中标接龙红包，冻结扣除：'.($value['money']+$value['water_money']),
                    'type' => 1,
                    'change_type' => $insert['lottery_type'],
                    'piping' => $value['crowd'],
                ];


                //如果发送失败。当前回滚不修改 继续下一次
                BotJieLongRedEnvelopeService::getInstance()->haplessUserSetSend($insert, $lotteryJoinUserUpdate, $lotteryJoinUpdate,$moneyLog);

            }
        }
    }


    protected function getHaplessUserList($list)
    {
        $ListUpdate = [];//需要更正的 红包数据 倒霉蛋的
        $haplessUserList = [];//倒霉蛋列表
        $setListUpdate = [];//没有倒霉蛋的列表。直接修改数据库的。特殊情况，应该是不会产生数据的，以防万一
        foreach ($list as $key => $value) {
            $update = [];
            $haplessUser = [];

            if ($value['hapless_user_id'] == 0) {
                //合规,查询获取用户获得金额最大的用户，选取作为倒霉蛋
                $haplessUser = LotteryJoinUserModel::getInstance()->getUserMaxMoney(['lottery_id' => $value['id']]);
                if (!empty($haplessUser)) {
                    $haplessUserList[$haplessUser['lottery_id']] = $haplessUser;
                }
                //合规 的全部改为已经获取到倒霉蛋
                $update = [
                    'id' => $value['id'],
                    'is_status' => LotteryJoinModel::IS_STATUS_JL,
                    'message_id' => $value['message_id'],
                    'crowd' => $value['crowd'],
                    'jl_number' => $value['jl_number'],
                    'money' => $value['money'],
                    'water_money' => $value['water_money'],
                    'join_num' => $value['join_num'],
                    'expire_at' => $value['expire_at'],
                    'jl_pid_id' => $value['jl_pid_id'],
                ];
                $ListUpdate[] = $update;
                continue;
            } else {
                //不合规的全部更改状态
                $update = [
                    'id' => $value['id'],
                    'is_status' => LotteryJoinModel::IS__STATUS_END_JL,
                    'message_id' => $value['message_id'],
                ];
                //$ListUpdate[] = $update;
                $setListUpdate[] = $update;
                continue;
            }
            continue;
        }
        return [$haplessUserList, $ListUpdate, $setListUpdate];
    }
}