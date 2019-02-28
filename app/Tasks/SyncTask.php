<?php
/**
 * This file is part of Swoft.
 *
 * @link https://swoft.org
 * @document https://doc.swoft.org
 * @contact group@swoft.org
 * @license https://github.com/swoft-cloud/swoft/blob/master/LICENSE
 */

namespace App\Tasks;


use App\Models\Logic\FlashairWorker;
use Swoft\Task\Bean\Annotation\Scheduled;
use Swoft\Task\Bean\Annotation\Task;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;
use GuzzleHttp\DefaultHandler;
use Swoft\Core\Coroutine;

//DefaultHandler::setDefaultHandler(SwooleHandler::class);

/**
 * Sync task
 *
 * @Task("sync")
 */
class SyncTask
{
    private static $working = false;

    /**
     * crontab定时任务
     * 每一秒执行一次
     *
     * @Scheduled(cron="* * * * * *")
     */
    public function deliverCo()
    {
        if (self::$working) {
            return;
        }
        self::$working = true;
        \Swoft::$server->sendToAll("deliverCo started!");
        //Coroutine::create(function () {
        //    while (true) {
                try {
                    \Swoft::$server->sendToAll("trying to connect flashair!");
                    //echo "trying to connect flashair!\n";
                    $worker = new FlashairWorker('/storage');
                    $worker->start();
                } catch (\Exception $e) {
                    echo 'Error:' . $e->getMessage();
                    \Swoft::$server->sendToAll( 'Error:' . $e->getMessage());
                } finally {
                    self::$working = false;
                }
        //    }
        //});
    }
}
