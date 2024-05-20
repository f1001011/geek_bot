<?php

namespace app\controller;

use app\service\log\MoneyLog;

class ApiUserMoneyLog extends ApiBase
{
    //获取用户信息

    public function joinUserLog()
    {
        $tgId = $this->request->post('tg_id',$this->request->user_info['tg_id']);
        $page = $this->request->post('page', 1);
        $size = $this->request->post('size', 10);
        if ($tgId <= 0) {
            fail([], 'tg_id 错误');
        }
        //查询用户领取列表
        $data = MoneyLog::getInstance()->joinUserLog($tgId, $page, $size);
        success($data);
    }

    public function joinLog()
    {
        $tgId = $this->request->post('tg_id',$this->request->user_info['tg_id']);
        $page = $this->request->post('page', 1);
        $size = $this->request->post('size', 10);
        if ($tgId <= 0) {
            fail([], 'tg_id 错误');
        }
        //查询用户发红包列表
        $data = MoneyLog::getInstance()->joinUserLog($tgId, $page, $size);
        success($data);
    }

    public function balanceInquiry()
    {
        //查询用户余额
        $tgId = $this->request->post('tg_id',$this->request->user_info['tg_id']);
        if ($tgId <= 0) {
            fail([], 'tg_id 错误');
        }
        $data = MoneyLog::getInstance()->balanceInquiry($tgId);
        success($data);
    }

    //资金日志
    public function balanceLog()
    {
        //查询用户余额
        $tgId = $this->request->post('tg_id',$this->request->user_info['tg_id']);
        $page = $this->request->post('page', 1);
        $size = $this->request->post('size', 10);
        if ($tgId <= 0) {
            fail([], 'tg_id 错误');
        }

        $data = MoneyLog::getInstance()->balanceLog($tgId,$page,$size);
        success($data);
    }

    //今日报表。 今日发支出，中雷支出。总领取金额
    public function dayForms()
    {

    }
}