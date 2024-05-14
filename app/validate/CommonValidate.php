<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class CommonValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'name'  => 'require|max:25',
        'money'  => 'require|float',
        'people'=>'max:100',
        'num'=>'require|number',
        'crowd'=>'require|integer',
        'expire_at'=>'integer',
        'start_at'=>'require|date',
        'red_id'=>'require|integer',
        'user_id'=>'require',
        'lottery_type'=>'require|number',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
//        'name.require' => '名称必须',//name-require
//        'money.require' => '名称必须',//money-require
//        'people.max' => '名称必须',//money-max
//        'name.max' => '名称必须',//name-max
//        'num.require' => '名称必须',//num-require
//        'num.number' => '名称必须',//num-number
//        'crowd.require' => '名称必须',//crowd-require
//        'crowd.integer' => '名称必须',//crowd-integer
//        'expire_at.integer' => '名称必须',//expire_at-integer
//        'start_at.require' => '名称必须',//expire_at-require
//        'start_at.date' => '名称必须',//expire_at-date
//        'red_id.require' => '名称必须',//red_id-require
//        'red_id.integer' => '名称必须',//red_id-integer
//        'user_id.integer' => '名称必须',//user_id-integer

    ];

    protected $scene = [
        'send-bot-red'  =>  ['people','num','crowd','money','expire_at','lottery_type'],
        'set-send-bot-red'  =>  ['red_id','lottery_type'],
    ];
}
