<?php
/**
 * Created by 董博明
 * Author: 董博明
 * Date: 2018/1/5
 * Time: 17:54
 */

namespace app\lib\exception;


class ImageException extends BaseException
{
    // public $code = 404;
    public $msg = '图片信息不存在';
    public $solelyCode = 204000;
}