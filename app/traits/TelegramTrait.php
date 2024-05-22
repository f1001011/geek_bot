<?php

namespace app\traits;

//飞机配置
use app\common\CacheKey;
use app\model\LotteryJoinUserModel;
use app\model\UserModel;
use think\Exception;
use think\facade\Cache;

trait TelegramTrait
{
    //管理员发送红包
    public function sendRrdBotRoot(int $startNum = 0, int $endNum = 0, string $param = '', string $crowd = '', $mine = '', $status = false)
    {
        $string = "($endNum/$startNum)";
        if ($startNum <= $endNum) {
            $string .= language('yqg');
        }
        if (!empty($mine)) {
            //雷号码
            $string .= '💣 ' . $mine;
        }

        if ($status) {
            $string .= language('yjs');
        }

        $one = [
            [
                ['text' => $string, 'callback_data' => config('telegram.bot-binding-red-string-one') . $param],
                //['text' => language('jrpt'), 'login_url' => $loginUrl]
            ],
        ];
        return array_merge($one, $this->menu());
    }


    //主动发送 群红包消息
    public function sendRrdBot(string $crowd = '')
    {

        //合并两个数组
        return $this->menu();
    }


    protected function menu()
    {
        return [
            [
                //['text' => '发送红包', 'url' => "https://t.me/$username"],
                ['text' => '发送红包', 'url' => config('telegram.bot-binding-active-url-in-one')],
                ['text' => '今日报表', 'callback_data' => 'myReportLog'],
                ['text' => '余额', 'callback_data' => 'myBalance'],
                ['text' => '更多游戏', 'url' => config('telegram.bot-binding-game-url-one')],
            ],
            [
                ['text' => '游戏充值', 'url' => config('telegram.bot-binding-recharge-url-one')],
                ['text' => '提取奖金', 'url' => config('telegram.bot-binding-carry-url-one')],
                ['text' => '联系客服', 'url' => config('telegram.bot-binding-kefue-url-one')],
            ],
        ];
    }

    //发起抢红包信息 telegram 展示
    public function copywriting($data)
    {
        $money = $data['money'];
        $jsonUser = $data['in_join_user'];
        $username = $data['username'];
        $on = $data['activity_on'];

        $string = language('title-hbo');
        //是否固定了抢红包的人
        if (empty($jsonUser)) {
            return $string . language('flgzsorfl', "<b>$username</b>", "{$money}U", $on);//福利红包
        }
        $string .= language('flgzsorzs', "<b>$username</b>", "{$money}U", $on);//专属红包

        $str = '';//装用户
        $jsonUser = explode(',', $jsonUser);
        //通过userID获取用户昵称
        $userList = UserModel::getInstance()->whereIn('tg_id', $jsonUser)->select();
        foreach ($userList as $Key => $value) {
            $date = date('H:i:s');
            $str .= language('klq', $money, $date, $value['username']);

        }
        return $string . $str;
    }

    //地雷红包发送文案
    public function zdCopywriting($money = 0, $username = '', $data = [])
    {
        $string = language('title-hbo') . language('flgzsordl', "<b>$username</b>", "{$money}U", $data['activity_on']);
        return $string;
    }

    //地雷红包用户完成过后文案 结束
    public function zdCopywritingEdit($data)
    {
        $money = $data['money'];
        $redId = $data['id'];
        $username = $data['username'];
        $on = $data['activity_on'];
        $number = $data['red_password'];

        $string = language('title-hbo') . language('flgzsordlend',
                "<b>$username</b>",
                "{$money}U",
                config('telegram.bot-binding-red-zd-rate'), $number, $on
            );

        //组装中奖盈亏
        //1 获取用户发出的金额
        //2 获取用户赔了多少钱
        $centreMoney = LotteryJoinUserModel::getInstance()->getCountRepay($redId);
        $centreString = language('flgzsordlendy', $centreMoney, $money, $centreMoney - $money);
        //组装中奖人
        //获取中奖人名单
        //查询redis是否存在领取信息，不存在查询数据库
        $userList = Cache::SMEMBERS(sprintf(CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER, $redId));
        $str = '';

        if (!empty($userList)) {
            foreach ($userList as $Key => $value) {
                $value = @json_decode($value, true);
                $str .= language('flgzsordlendxq', $value['user_repay'] == 0 ? '🥰' : '🥶', $value['money'], $value['user_name']);
            }
            return $string . $str . $centreString;
        }

        //无 redis 信息时
        $userList = LotteryJoinUserModel::getInstance()->getDataList(['lottery_id' => $redId]);
        foreach ($userList as $Key => $value) {
            $str .= language('flgzsordlendxq', $value['user_repay'] == 0 ? '🥰' : '🥶', $value['money'], $value['user_name']);
        }
        return $string . $str . $centreString;
    }


    //用户领取红包  发起抢红包信息 telegram 展示
    public function queryPhotoEdit($data, $toMoney, $userInfo = [], $false = true)
    {
        $money = $data['money'];
        $username = $data['username'];
        $redId = $data['id'];
        $on = $data['activity_on'];

        $string = language('title-hbo');
        //是否固定了抢红包的人
        if ($data['lottery_type'] == 1) {
            $string .= language('flgzsorzs', "<b>$username</b>", "{$money}U", $on); //专属
        } else {
            $string .= language('flgzsorfl', "<b>$username</b>", "{$money}U", $on); //福利
        }

        $str = '';
        $date = date('H:i:s');
        //查询redis是否存在领取信息，不存在查询数据库
        $userList = Cache::SMEMBERS(sprintf(CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER, $redId));
        //有redis 信息时
        if (!empty($userList)) {
            $str .= "🏆{$toMoney}U({$date}-{$userInfo['username']}" . language('yq') . ")\n"; //已抢
            foreach ($userList as $Key => $value) {
                $value = @json_decode($value, true);
                //如果不需要公布中奖名单
                $str = language('yilingjiang', $false ? $value['money'] . 'U' : '', $date, $value['user_name']);
            }
            return $string . $str;
        }

        //无 redis 信息时
        $userList = LotteryJoinUserModel::getInstance()->getDataList(['lottery_id' => $redId]);
        if (empty($userList)) {
            //用户不存在是。只展示当前的
            //如果不需要公布中奖名单
            $str = language('yilingjiang', $false ? $toMoney . 'U' : '', $date, $userInfo['username']);
            return $string . $str;
        }

        foreach ($userList as $Key => $value) {
            $str = language('yilingjiang', $false ? $value['money'] . 'U' : '', $date, $value['user_name']);
        }
        return $string . $str;
    }

    //接龙红包文案 $money 红包额度  $moneyT 红包额度+水钱  $waterMoney 水钱 $num 抢的人数 $water 扣税率
    public function jlCopywriting($money = 0, $waterL = 0, $num = '', $username = '', $redInfo = [])
    {
        $string = language('title-hbo');
        $string .= language('tgjlhbwasend', $username, $money, $num, $waterL);
        return $string;
    }

    //接龙红包领取完开奖展示

    public function jlqueryPhotoEdit($money = 0, $waterL = 0, $num = 0, $toNum = 0, $username = '', $userInfo = [], $redId = 0, $false = true)
    {
        $string = language('title-hbo');
        $string .= language('tgjlhbwasend', $username, $money, $num, $waterL);
        $str = '';
        $date = date('H:i:s');
        //查询redis是否存在领取信息，不存在查询数据库
        $userList = Cache::SMEMBERS(sprintf(CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER, $redId));
        //有redis 信息时
        if (!empty($userList)) {

            ###############################
            //红包领取结束 判断展示的图标。谁领取的最多 展示不一样 接龙红包，谁领的最多，谁就中标
            if ($false) {
                $max = ['money' => 0];
                foreach ($userList as $k => $v) {

                    //取出所有的金额。获取最大的
                    $v = @json_decode($v, true);

                    if ($v['money'] > $max['money']) {

                        $max = $v;
                    }
                }

                traceLog($max, "xxxxxxxxxxxx");
            }

            ###########################

            foreach ($userList as $Key => $value) {
                $value = @json_decode($value, true);
                //如果不需要公布中奖名单
                if (!$false) {
                    $str .= language('yilingjiang', '🥰', '', $date, $value['user_name']);
                } else {

                    $t = '🥰';
                    if ($max['user_id'] == $value['user_id']) {
                        $t = '🥶';
                    }

                    $str .= language('yilingjiang', $t, $value['money'] . 'U', $date, $value['user_name']);
                }

            }
            return $string . $str;
        }
        return $string . $str;
    }


    //验证登录用户是否正确
    function checkTelegramAuthorization($auth_data)
    {
        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        $data_check_arr = [];
        foreach ($auth_data as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key = hash('sha256', config('telegram.bot-token'), true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);
        if (strcmp($hash, $check_hash) !== 0) {
            throw new Exception('Data is NOT from Telegram');
        }
        if ((time() - $auth_data['auth_date']) > 86400) {
            throw new Exception('Data is outdated');
        }
        return $auth_data;
    }

    function saveTelegramUserData($auth_data)
    {
        $auth_data_json = json_encode($auth_data);
        Cache::set(sprintf(CacheKey::REDIS_TG_USER, $auth_data['id']), $auth_data_json, CacheKey::REDIS_TG_USER_TTL);
    }

    function getTgUser($tgId)
    {
        $user = Cache::get(sprintf(CacheKey::REDIS_TG_USER, $tgId));
        if (empty($user)) {
            return [];
        }
        return json_decode($user, true);
    }

    function getUser($tgId)
    {
        return UserModel::getInstance()->getDataUserOne(['tg_id' => $tgId]);
    }
}
