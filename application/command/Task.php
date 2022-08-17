<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Task extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('task');
        // 设置参数
        
    }

    protected function execute(Input $input, Output $output)
    {

        $taskModel = new \app\common\model\CommonTaskModel();
        $sql = $taskModel->autoSetTaskStatus();

        $taskIngModel  = new \app\common\model\UserTaskIngModel();
        $taskModel  = new \app\common\model\UserTaskModel();

        $rows = $taskIngModel->whereTime('end_time', '<', time())->field('uid')->select();
        $num = 0;
        if($rows) {
            foreach($rows as $v) {
                if($taskModel->where(['uid' => $v['uid'], 'result' => 0])->update(['result' => 2])) {
                    $taskIngModel->where(['uid' => $v['uid']])->delete();
                    $num++;
                }
            }
        }


        // $rows =  $model->autoAbandon(time());
    	// 指令输出
    	$output->writeln('task num = '. $num);
    }
}
