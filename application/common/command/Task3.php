<?php

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Task3 extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('app\common\command\task3');
        // 设置参数
        
    }

    protected function execute(Input $input, Output $output)
    {
    	// 指令输出
    	$output->writeln('app\command\task3');
    }
}
