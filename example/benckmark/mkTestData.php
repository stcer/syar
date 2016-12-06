<?php

require __DIR__ . '/lib.php';
require __DIR__ . '/../service/Db.php';

/**
 * @param $c
 * @param $n
 */
function insertTestData($c, $n) {
    $step = $n / $c;
    $pm = new \syar\example\benckmark\SimpleProcessorManager();
    $pm->run($c, function(swoole_process $worker) use($step){
        echo "Worker {$worker->pid} start\n";
        $dbo = \syar\example\service\Db::getDb();
        for($x = 0; $x < $step; $x++){
            $name = 'title_' . ($x);
            $age = rand(10, 50);
            $sex = rand(1, 2);
            $sql = "insert tmp_1(title, sex, age) value ('{$name}', {$sex}, {$age})";
            $dbo->query($sql);
        }
        $worker->exit(0);
    });
}

// 50进程生成1万测试数据
insertTestData(50, 10000);