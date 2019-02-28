<?php
/**
 * Created by PhpStorm.
 * User: R720
 * Date: 2018/11/6
 * Time: 14:25
 */

namespace App\Models\Logic;


class FlashairItem
{
    public $isDirectory = false;
    public $fileSize = 0;
    public $name = '';
    public $createdAt = 0;

    public function __construct($isDirectory, $name, $fileSize, $createdAt)
    {
        $this->isDirectory = $isDirectory;
        $this->fileSize = $fileSize;
        $this->name = $name;
        $this->createdAt = $createdAt;
    }
}