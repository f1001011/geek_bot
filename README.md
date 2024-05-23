
###########################################################################
配置文件
config/telegram  配置一切参数
    database 数据库配置
    cache   redis配置
    queue   消息队列redis配置
###########################################################################



############################################################################
消息队列。守护进程执行
php think queue:listen --queue CommandJob
php think queue:listen --queue OpenLotteryJoinJob
############################################################################


############################################################################
计划任务
没有发送出去的红包(及时发出去)  1-2分钟执行一次
php /www/wwwroot/redapi.tggame.vip/geek_test/think redautosend

用户未领取完红包(返回) 10分钟左右执行一次
php /www/wwwroot/redapi.tggame.vip/geek_test/think redunclaimed

资金日志返回押金任务(主要接龙红包)  1 分钟执行一次
php /www/wwwroot/redapi.tggame.vip/geek_test/think redusermoneylog

执行自动选择倒霉蛋发送命令(接龙红包)  2分钟执行一次，兜底消息队列，防止队列执行失败
php /www/wwwroot/redapi.tggame.vip/geek_test/think selhaplesstask


结束已经过期或者领完的红包抽奖状态(防止有红包状态没更新) 2分钟执行一次
php /www/wwwroot/redapi.tggame.vip/geek_test/think redautoclose

##############################################################################