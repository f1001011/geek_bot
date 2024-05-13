<?php

namespace app\model;

class MoneyLogModel extends BaseModel
{
    //用户金额消费记录日志
    public $table = 'money_log';

    public function setInsert($data)
    {

        //插入日志
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        //$data['status'] = 0;//插入都是0
        return $this->insertGetId($data);
    }

    public function setInsertAll($data)
    {
        return $this->insertAll($data);
    }
}