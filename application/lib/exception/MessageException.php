<?php
/**
 * Created by 董博明
 * Author: 董博明
 * Date: 2018/2/11
 * Time: 17:22
 */

namespace app\lib\exception;


class MessageException extends BaseException
{
    // public $code = 404;
    public $msg = '指定留言不存在';
    public $solelyCode = 207000;
}