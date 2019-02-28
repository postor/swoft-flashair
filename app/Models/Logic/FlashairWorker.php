<?php
/**
 * Created by PhpStorm.
 * User: R720
 * Date: 2018/11/6
 * Time: 14:15
 */

namespace App\Models\Logic;


class FlashairWorker
{
    public $remoteHost;
    public $remotePath;
    public $storagePath;

    public function __construct($storagePath = 'G:\\storage', string $remotePath = '/DCIM', string $remoteHost = 'flashair')
    {
        $this->remoteHost = $remoteHost;
        $this->remotePath = $remotePath;
        $this->storagePath = $storagePath;
    }

    public function start()
    {
        $root = new FlashairService($this->remoteHost, $this->remotePath);
        $contents = $root->ls();
        foreach ($contents as $key => $value) {
            /* @var $value FlashairItem */
            if ($value->isDirectory) {
                $worker = new FlashairWorker($this->storagePath, $this->remotePath . '/' . $value->name, $this->remoteHost);
                $worker->start();
                continue;
            }

            list($filetype) = \wapmorgan\FileTypeDetector\Detector::detectByFilename($value->name);
            if (!$filetype) {
                continue;
            }

            $timeStart = time();
            try {
                $formated = date('h:i:s', $timeStart);
                echo "saving  {$this->remotePath}/{$value->name} to {$this->storagePath} time:{$formated} size:{$value->fileSize}\n";
                \Swoft::$server->sendToAll("saving  {$this->remotePath}/{$value->name} to {$this->storagePath} time:{$formated} size:{$value->fileSize}");
                $root->save($value->name, $value->createdAt, $this->storagePath);
                $root->remove($value->name);
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                //echo "save fail!";
                \Swoft::$server->sendToAll("save fail!");
            }
            $cost = time() - $timeStart;
            //echo "cost {$cost} seconds \n";
            \Swoft::$server->sendToAll("cost {$cost} seconds");
        }
    }

}