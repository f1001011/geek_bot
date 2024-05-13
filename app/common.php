<?php
// 应用公共文件

function success(array $data = [],$message = 'message', int $code = 200)
{
    echo json_encode(['data' => $data,'message'=>$message, 'code' => $code]);
    die;
}

function fail(array $data = [],$message = 'message', int $code = 500)
{
    echo json_encode(['data' => $data,'message'=>$message, 'code' => $code]);
    die;
}

function language(string $name = '',...$values)
{
    if (empty($values)){
        return lang($name);
    }
    return  sprintf(lang($name),...$values);
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

function getRedEnvelopeOn(int $length = 20,$string = ''): string
{
    //随机字符集
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $on = '';
    for ($i = 0; $i < $length; $i++) {
        $on .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    $date = date('YmdHis');
    return $on.$date.$string;
}

function traceLog($message,$lv=''){
    trace($message,$lv.'-RESPONSE_ID:'.REQUEST_ID);
}
function traceLogs($message,$lv = 'info',$channel = 'job'){
    \think\facade\Log::channel($channel)->$lv($message);
}