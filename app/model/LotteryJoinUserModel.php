<?php

namespace app\model;

class LotteryJoinUserModel extends BaseModel
{
    public $table = 'lottery_join_user';



    public function setUpdate($map = [], $data = [])
    {
        //$data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (empty($data)){
            $this->update($data);
        }
        return $this->where($map)->update($data);
    }

    //获取用户获得金额最大的用户，作为接龙红包倒霉蛋
    public function getUserMaxMoney($map = []){
        $find = $this->where($map)->order('money','desc')->find();
        return empty($find) ? []:$find->toArray();
    }

    //更改领奖信息。除了 倒霉蛋，其他用户的 押金返回状态全部改为未返回，方便任务 跑返回押金
    public function updateStatusDeposit($id,$lotteryId){
        return $this->where('id','<>',$id)->where('lottery_id',$lotteryId)->update(['status'=>1]);
    }

    //用户领取红包
    public function setInsert($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->insertGetId($data);
    }

}
