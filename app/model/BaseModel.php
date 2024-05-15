<?php

namespace app\model;


use think\Model;

class BaseModel extends Model
{
    protected static $_instance;

    public static function getInstance()
    {
        $localClass = new static();
        if ($localClass::$_instance instanceof $localClass) {
            return $localClass::$_instance;
        } else {
            $localClass::$_instance = new static();
            return self::$_instance;
        }
    }


    public function getDataList($map, $field = '*')
    {
        return $this->where($map)->field($field)->order('id', 'desc')->select()->toArray();
    }

    public function getDataPageList($map, $order = [], $page = 1, $limit = 10, $field = '*')
    {
        return $this->where($map)->field($field)->order($order)->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);
    }

    public function delDataOne($map)
    {
        return $this->where($map)->delete();
    }

    public function getDataOne($map, $field = '*')
    {
        return $this->where($map)->field($field)->find();
    }

    public function setInsert($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->insertGetId($data);
    }

    public function setUpdate($map = [], $data = [])
    {
        //$data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->where($map)->update($data);
    }

    public function setInsertOrUpdate($map, $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        //查询数据是否存在
        $model = $this->where($map)->find();
        return $model->save($data);
    }

    public function updateAll($data)
    {
        return $this->saveAll($data);
    }

    public function updateOne($data)
    {
        return $this->save($data);
    }

}
