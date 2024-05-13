<?php

namespace app\traits;

use app\validate\CommonValidate;
use think\exception\ValidateException;

trait CommonTrait
{
    private static $instance;
    public static function getInstance()
    {
        if (static::$instance == null) {
            static::$instance = new self();
        }
        return static::$instance;
    }


    public function only($name, $type = 'post')
    {
        $param = $this->request->$type();
        if (empty($name))
            return [];
        is_string($name) && $name = explode(',', $name);

        $item = [];
        foreach ($name as $key => $val) {
            if ($param[$val] == '')
                continue;
            $item[$val] = $param[$val];
        }
        //$item=array_filter($item);
        return $item;
    }

    public function validate($name, $data)
    {
        try {
            if (empty($name)) {
                validate(CommonValidate::class)->check($data);

            } else {
                validate(CommonValidate::class)->scene($name)->check($data);
            }
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return $e->getError();
        }
        return true;
    }
}