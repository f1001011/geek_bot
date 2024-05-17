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
    public function sendRrdBotRoot(int $startNum = 0, int $endNum = 0, string $param = '', string $crowd = '')
    {
        $string = "($endNum/$startNum)";
        if ($startNum <= $endNum) {
            $string .= language('yqg');
        }

        $loginUrl = [
            'url' => config('telegram.bot-binding-active-url-one') . '?crowd=' . $crowd, // 你的登录页面 URL
            'forward_text' => '登录成功', // 可选，用户登录成功后，你想让 bot 发送的消息文本
            'request_write_access' => true // 可选，请求写访问权限
        ];

        $one = [
            [
                ['text' => $string, 'callback_data' => config('telegram.bot-binding-red-string-one') . $param],
                //['text' => language('jrpt'), 'login_url' => $loginUrl]
            ],
        ];
        return array_merge($one, $this->menu());
//        return [
//            [
//                ['text' => $string, 'callback_data' => config('telegram.bot-binding-red-string-one') . $param],
//                //['text' => language('jrpt'), 'login_url' => $loginUrl]
//            ],
//            [
//                ['text' => '发送红包', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?qwe=123'],
//                ['text' => '游戏充值', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?qwe=123'],
//                ['text' => '提取奖金', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?qwe=123'],
//            ],
//            [
//                ['text' => '更多游戏', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?qwe=123'],
//                ['text' => '邀请好友', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?qwe=123'],
//                ['text' => '联系客服', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?qwe=123'],
//            ],
//            [
//                ['text' => '点击跳转', 'url' => 'https://t.me/red_app_test_bot/myRedTestName'. $param],
//
//            ]
//        ];
    }


    //主动发送 群红包消息
    public function sendRrdBot(string $crowd = '')
    {
        $loginUrl = [
            //'url' => config('telegram.bot-binding-active-url-one').'?crowd='.$crowd, // 你的登录页面 URL
            'url' => 'https://t.me/red_app_test_bot/myRedTestName?crowd=' . $crowd, // 你的登录页面 URL
            'forward_text' => '登录成功', // 可选，用户登录成功后，你想让 bot 发送的消息文本
            //'bot_username' => 'YourBotUsername', // 可选，你的 bot 的用户名
            'request_write_access' => true // 可选，请求写访问权限
        ];

        $one = [
            [
                ['text' => language('jrpt'), 'login_url' => $loginUrl]
            ],
        ];
        //合并两个数组
        return array_merge($one, $this->menu());
    }

    public function myRedSend()
    {
        return [
            [
                ['text' => '我要发红包', 'url' => 'https://t.me/red_app_test_bot/myRedTestName'],
                ['text' => '主菜单', 'callback_data' => '/start']
            ]
        ];
    }

    protected function menu()
    {
        return [
            [
                //['text' => '发送红包', 'url' => "https://t.me/$username"],
                ['text' => '发送红包', 'callback_data' => "my_red_send"],
                ['text' => '游戏充值', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?id=123321'],
                ['text' => '提取奖金', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?id=123321'],
            ],
            [
                ['text' => '更多游戏', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?id=123321'],
                ['text' => '邀请好友', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?id=123321'],
                ['text' => '联系客服', 'url' => 'https://t.me/red_app_test_bot/myRedTestName?id=123321'],
            ],
        ];
    }

    //发起抢红包信息 telegram 展示
    public function copywriting($money = 0, $jsonUser = '', $username = '')
    {
        $string = '🧧' . language('title-hb') . '🧧' . "\n" . language('flgzsorfl', "<b>$username</b>", "{$money}U");
        //是否固定了抢红包的人
        if (empty($jsonUser)) {
            return $string;
        }
        $str = '';
        $jsonUser = explode(',', $jsonUser);
        //通过userID获取用户昵称
        $userList = UserModel::getInstance()->whereIn('tg_id', $jsonUser)->select();
        foreach ($userList as $Key => $value) {
            $date = date('H:i:s');
            //$str .= "🏆{$money}U({$date}-{$value['username']}" . language('klq') . ")\n";
            $str .= language('klq', $money, $date, $value['username']);

        }
        return $string . $str;
    }

    //用户领取红包  发起抢红包信息 telegram 展示
    public function queryPhotoEdit($money, $toMoney, $redId = 0, $username = '', $userInfo = [], $false = true)
    {
        $string = '🧧' . language('title-hb') . '🧧' . "\n" . language('flgzsorfl', "<b>$username</b>", "{$money}U");
        //$string = '🧧' . language('title-hb') . '🧧' . "\n" . '🕴<b>' . language('title-kf') . '</b>' . language('flg', "{$money}U") . "\n";
        //是否固定了抢红包的人
        $str = '';
        $date = date('H:i:s');
        //查询redis是否存在领取信息，不存在查询数据库
        $userList = Cache::SMEMBERS(sprintf(CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER, $redId));
        //有redis 信息时
        if (!empty($userList)) {
            $str .= "🏆{$toMoney}U({$date}-{$userInfo['username']}" . language('yq') . ")\n";
            language('flgzsorfl', "<b>$username</b>", "{$money}U");
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
        $string = '🧧' . language('title-hb') . '🧧' . "\n";
        $string .= language('tgjlhbwasend', $username, $money, $num, $waterL);
        return $string;
    }

    //接龙红包领取完开奖展示
    public function jlqueryPhotoEdit($money = 0, $waterL = 0, $num = 0, $toNum = 0, $username = '', $toMoney = 0, $redId = 0, $false = true)
    {
        $string = '🧧' . language('title-hb') . '🧧' . "\n";
        $string .= language('tgjlhbwasend', $username, $money, $num, $waterL);
        $str = '';
        $date = date('H:i:s');
        //查询redis是否存在领取信息，不存在查询数据库
        $userList = Cache::SMEMBERS(sprintf(CacheKey::REDIS_TELEGRAM_RED_RECEIVE_USER, $redId));
        //有redis 信息时
        if (!empty($userList)) {
            //$str .= "🏆{$toMoney}U({$date}-{$username}" . language('yq') . ")\n";
            foreach ($userList as $Key => $value) {
                $value = @json_decode($value, true);
                //如果不需要公布中奖名单
                $str .= language('yilingjiang', $false ? $value['money'] . 'U' : '', $date, $value['user_name']);
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
