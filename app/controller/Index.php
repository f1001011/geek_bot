<?php
namespace app\controller;



use app\facade\BotFacade;
use app\model\testNameModel;
use app\service\BotJieLongRedEnvelopeService;
use app\validate\CommonValidate;
use think\exception\ValidateException;


class Index extends ApiBase
{

    public function index()
    {

















    die;
        $str = BotJieLongRedEnvelopeService::getInstance()->jlCopywriting(100,'0.2',5,'你好');
        dump($str);die;
        $param = $this->request->only(['lang']);

        try {
            validate(CommonValidate::class)->scene('edit')->check($param);
        } catch (ValidateException $e) {
            fail([],$e->getError());
        }
        testNameModel::getInstance()->getOne(1);
        dump(1);die;
    }

    public function edit()
    {

        dump(2);die;
    }

    public function add()
    {
        dump(3);die;
    }
}
