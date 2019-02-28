<?php
/**
 * Created by PhpStorm.
 * User: R720
 * Date: 2018/11/5
 * Time: 17:50
 */

namespace App\Models\Logic;


class FlashairService
{
    protected $remoteHost;
    protected $remotePath;

    public function __construct(string $remoteHost, string $remotePath)
    {
        $this->remoteHost = $remoteHost;
        $this->remotePath = $remotePath;
    }

    /**
     * Scans for files inside the directory without traversing subdirectories. Returns the filename and the file
     * creation date as an associative array.
     *
     * @return array
     */
    public function ls(): array
    {
        $contents = [];
        $url = sprintf('http://%s/command.cgi?op=100&DIR=%s', $this->remoteHost, rawurlencode($this->remotePath));
        $response = self::httpGet($url);
        if (!$response) {
            \Swoft::$server->sendToAll('Cannot read directory list.');
            throw new \Exception('Cannot read directory list.');
        }
        foreach (preg_split('/\r?\n/', $response) as $entry) {
            $entryProperties = str_getcsv($entry);
            if (count($entryProperties) < 3) {
                //Invalid entry
                continue;
            }

            $year = (($entryProperties[4] & 0b1111111000000000) >> 9) + 1980;
            $month = ($entryProperties[4] & 0b0000000111100000) >> 5;
            $day = ($entryProperties[4] & 0b0000000000011111);
            $hours = ($entryProperties[5] & 0b1111100000000000) >> 11;
            $minutes = ($entryProperties[5] & 0b0000011111100000) >> 5;
            $seconds = ($entryProperties[5] & 0b0000000000011111) * 2;
            $isoTime = sprintf('%d-%d-%d %d:%d:%d', $year, $month, $day, $hours, $minutes, $seconds);
            $time = strtotime($isoTime);
            if ($entryProperties[3] & 16) {
                $contents[$entryProperties[1]] = new FlashairItem(true, $entryProperties[1], 0, $time);
                //Directory
                continue;
            }
            $contents[$entryProperties[1]] = new FlashairItem(false, $entryProperties[1], intval($entryProperties[2]), $time);
        }
        return $contents;
    }

    public function get(string $filename, int $createdAt, string $localSaveDir)
    {
        $remoteUrl = sprintf('http://%s/%s/%s', $this->remoteHost, $this->remotePath, $filename);
        @mkdir($localSaveDir, 0777, true);
        $localSavePath = $localSaveDir . DIRECTORY_SEPARATOR . $filename;
        echo "saving to $localSavePath \n";
        \Swoft::$server->sendToAll("saving to $localSavePath");
        if (self::httpSave($remoteUrl, $localSavePath)) {
            touch($localSavePath, $createdAt);
        } else {
            \Swoft::$server->sendToAll(sprintf('Cannot copy file from %s to %s', $remoteUrl, $localSavePath));
            throw new \Exception(sprintf('Cannot copy file from %s to %s', $remoteUrl, $localSavePath));
        }
    }

    public function remove(string $filename)
    {
        $remoteUrl = sprintf('http://%s/upload.cgi?DEL=%s/%s', $this->remoteHost, $this->remotePath, $filename);
        self::httpGet($remoteUrl);
    }

    public function save(string $filename, int $createdAt, string $localPathWorkingDir)
    {
        list($filetype) = \wapmorgan\FileTypeDetector\Detector::detectByFilename($filename);
        $filetype = $filetype ? $filetype : '其他';
        $date = str_replace('/', DIRECTORY_SEPARATOR, date('Y/m/d', time()));
        $dir = $localPathWorkingDir . DIRECTORY_SEPARATOR . $filetype . DIRECTORY_SEPARATOR . $date;
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($path)) {
            if (!strpos($filename, '_' . $createdAt) >= 0) {
                //如果重复且不包含时间戳，则追加时间戳
                $filename .= '_' . $createdAt;
                $path = $dir . DIRECTORY_SEPARATOR . $filename;
            }
        }
        if (file_exists($path)) {
            //如果仍然存在则删除
            @unlink($path);
        }
        $this->get($filename, $createdAt, $dir);
    }

    public static function httpGet($url)
    {
        $client = self::getHttpClient();
        $res = $client->request('GET', $url);
        return $res->getBody();
    }

    public static function httpSave($url, $path)
    {
        $client = self::getHttpClient();
        $client->request('GET', $url, ['sink' => $path, 'progress' => function (
            $downloadTotal,
            $downloadedBytes,
            $uploadTotal,
            $uploadedBytes
        ) {
            //$percentage = $downloadedBytes/$downloadTotal*100;
            //echo "\033[20D";
            //echo str_pad("{$downloadedBytes}/{$downloadTotal}", 20);
            \Swoft::$server->sendToAll("{$downloadedBytes}/{$downloadTotal}");
        },]);
        return true;
    }

    private static $httpClient;

    public static function getHttpClient()
    {
        if (!self::$httpClient) {
            self::$httpClient = new \GuzzleHttp\Client();
        }
        return self::$httpClient;
    }
}