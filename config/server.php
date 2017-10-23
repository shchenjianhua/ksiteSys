<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/9/16
 * Time: 9:11
 * Desc: -swoole服务配置
 */
 

use Lkk\Phalwoo\Server\ServerConst;

return [
    'server_name' => KSERVER_NAME,
    'server_vers' => KSERVER_VERS,
    'pid_dir' => RUNTDIR .'pids' .DS,

    //是否热更新服务代码,需inotify扩展
    'server_reload' => false,

    //http服务监听
    'http_server' => [
        'host' => '0.0.0.0',
        'port' => 6666, //6666
    ],

    //本系统日志,强制开启
    'sys_log' => [
        'name' => 'kslog', //日志名
        'file' => LOGDIR .'kslog.log', //日志文件路径
        'slow_request' => 500, //慢请求,毫秒
    ],

    //xhprof性能日志
    'xhprof_enable' => true, //是否开启xhprof
    'xhprof_ratio' => 1, //取样概率,1/N

    //pv访问次数记录
    'pv' => [
        //每日真实pv的redis缓存key
        'day_real_pv' => 'dayRealPv',
        //每日有效pv的redis缓存key
        'day_vali_pv' => 'dayValiPv',
        'times' => 10000, //TODO 每1W次更新数据表,每天23.55再入库
    ],

    //服务配置
    'server_conf' => [
        //守护进程化
        'daemonize' => 0,
        //线程数,=CPU核数
        'reactor_num' => 2,
        //worker进程数,为CPU的1-4倍
        'worker_num' => 4,
        //worker进程的最大任务数
        'max_request' => 1024,
        //服务最大允许的连接数
        'max_conn' => 1024,
        //task进程的数量
        'task_worker_num' => 5,
        //task进程的最大任务数,0不限制
        'task_max_request' => 1024,
        //task的数据临时目录
        'task_tmpdir' => '/tmp',
        //数据包分发策略
        'dispatch_mode' => 2,
        //Listen队列长度
        'backlog' => 128,
        //指定swoole错误日志文件
        'log_file' => LOGDIR .'swoole.log',
        //日志级别
        'log_level' => 0,
        //启用心跳检测,每隔N秒轮循一次
        'heartbeat_check_interval' => 5,
        //表示连接最大允许空闲的时间,需要比heartbeat_check_interval大
        'heartbeat_idle_time' => 10,

        //打开包长检测
        'open_length_check' => true,
        //包长度值的类型
        'package_length_type' => 'N',
        //最大数据包尺寸
        'package_max_length' => 2097152, //2M
        'package_length_offset' => 0,
        'package_body_offset' => 4,
        //管道通信的内存缓存区长度
        'pipe_buffer_size' => 33554432, //32M
        //发送缓存区尺寸
        'buffer_output_size' => 3145728, //3M

        //立即发往客户端连接,提升响应速度
        'open_tcp_nodelay' => 1,
        //启用CPU亲和性设置
        'open_cpu_affinity' => 1,
        //设置端口重用
        'enable_reuse_port' => 1,
    ],

    //定时任务
    'timer_tasks' => [
        //检测是否有其他进程正在停止服务
        [
            'type' => ServerConst::SERVER_TASK_TIMER,
            'message' => [
                'title' => 'checkStopping',
                'callback' => ['\Apps\Timers\Server','checkStopping'],
                'params' => [],
            ],
            'run_interval_time' => 1,
        ],

        //session入库redis
        [
            'type' => ServerConst::SERVER_TASK_TIMER,
            'message' => [
                'title' => 'writeSession',
                'callback' => ['\Apps\Timers\Server','writeSession'],
                'params' => [],
            ],
            'run_interval_time' => 0.2,
        ],

        //session数据清理
        [
            'type' => ServerConst::SERVER_TASK_TIMER,
            'message' => [
                'title' => 'sessionGc',
                'callback' => ['\Apps\Timers\Server','sessionGc'],
                'params' => [],
            ],
            'run_interval_time' => 30,
        ],

    ],

];