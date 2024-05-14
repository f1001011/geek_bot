<?php
// 应用公共文件

function success(array $data = [], $message = 'message', int $code = 200)
{
    echo json_encode(['data' => $data, 'message' => $message, 'code' => $code]);
    die;
}

function fail(array $data = [], $message = 'message', int $code = 500)
{
    echo json_encode(['data' => $data, 'message' => $message, 'code' => $code]);
    die;
}

function language(string $name = '', ...$values)
{
    if (empty($values)) {
        return lang($name);
    }
    return sprintf(lang($name), ...$values);
}

function curlPost(string $url, array $post_data = [], $type = 'http_build_query', $header = [])
{
    if (empty($url)) {
        return false;
    }
    if ($type == 'json') {
        $post_data = json_encode($post_data);
        $header = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($post_data)
        ];
    } else {
        $post_data = http_build_query($post_data);
    }
    $curl = curl_init();// 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url);// 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);// 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算法是否存在
    //    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);// 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);// 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);// 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1);// 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);// Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);// 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0);// 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 获取的信息以文件流的形式返回
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    $data = curl_exec($curl);//运行curl
    if (curl_errno($curl)) {
        trace(curl_error($curl), 'error');
    }
    curl_close($curl);
    trace($data, 'info');
    return $data;
}

function getRedEnvelopeOn(int $length = 20, $string = ''): string
{
    //随机字符集
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $on = '';
    for ($i = 0; $i < $length; $i++) {
        $on .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    $date = date('YmdHis');
    return $on . $date . $string;
}

function traceLog($message, $lv = '')
{
    trace($message, $lv . '-RESPONSE_ID:' . REQUEST_ID);
}

function traceLogs($message, $lv = 'info', $channel = 'job')
{
    \think\facade\Log::channel($channel)->$lv($message);
}

function hashSha($auth_data)
{
    $token = '';//密钥
    $check_hash = $auth_data['hash'];
    unset($auth_data['hash']);
    $data_check_arr = [];
    foreach ($auth_data as $key => $value) {
        $data_check_arr[] = $key . '=' . $value;
    }
    sort($data_check_arr);
    $data_check_string = implode("", $data_check_arr);
    $secret_key = hash('sha256', $token, true);
    $hash = hash_hmac('sha256', $data_check_string, $secret_key);
    if (strcmp($hash, $check_hash) !== 0) {
        throw new Exception('Data is NOT from Telegram');
    }
    if ((time() - $auth_data['auth_date']) > 86400) {
        throw new Exception('Data is outdated');
    }
    return $auth_data;
}

function getCookie($name = '')
{
    return $_COOKIE[$name];
}

function createGuid(): string
{
    $char_id = strtoupper(md5(uniqid(mt_rand(), true)));
    $hyphen = chr(45);
    return substr($char_id, 0, 8) . $hyphen
        . substr($char_id, 8, 4) . $hyphen
        . substr($char_id, 12, 4) . $hyphen
        . substr($char_id, 16, 4) . $hyphen
        . substr($char_id, 20, 12);
}

//生成token
function generateToken($data, $secretKey = '')
{
    $secretKey = config('app.user-secret-key');
    // 验证数据
    if (!is_array($data) || empty($secretKey)) {
        return false;
    }
    // 序列化数据
    $serializedData = json_encode($data);
    // 创建一个签名，这里使用了 HMAC-SHA256
    $signature = hash_hmac('sha256', $serializedData, $secretKey, true);
    // 将签名附加到数据上
    $tokenData = $serializedData . '.' . base64_encode($signature);
    // 对 token 进行 base64 编码以便于存储和传输
    $encodedToken = base64_encode($tokenData);

    return $encodedToken;
}

//解密token
function decryptToken($encodedToken, $secretKey = '')
{
    try {
        $secretKey = config('app.user-secret-key');
        // 解码 token
        $tokenData = base64_decode($encodedToken);
        // 分割数据和签名
        list($serializedData, $signatureBase64) = explode('.', $tokenData, 2);
        // 解码签名
        $signature = base64_decode($signatureBase64);
        // 验证签名
        $expectedSignature = hash_hmac('sha256', $serializedData, $secretKey, true);
        if (!hash_equals($signature, $expectedSignature)) {
            return false; // 签名不匹配，token 无效
        }
        // 解码数据
        $data = json_decode($serializedData, true);
    } catch (Exception $e) {
        fail([],language('sign error'));
    }
    return $data;
}