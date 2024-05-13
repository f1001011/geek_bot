<?php

namespace app\model;

class UserModel extends BaseModel
{
    public $table = 'users';


    public function incOrDec($id, $int)
    {
        if ($int > 0) {
            return $this->where('id', $id)->inc('balance', $int)->update();
        }
        return $this->where('id', $id)->dec('balance', $int)->update();
    }

    public function dec($id, $int)
    {
        return $this->where('id', $id)->dec('balance', $int)->update();
    }

    //$decBalance 用户余额需要减少多少，用户冻结多少金额
    public function userFreezeRedBalance($id,$decBalance,$freezeBalance){
        //用户领取红包，冻结金额操作
    }
}
