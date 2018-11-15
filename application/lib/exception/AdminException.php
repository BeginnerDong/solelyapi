<?php
/**
 * Created by 董博明.
 * User: 董博明
 * Date: 2018/1/6
 * Time: 14:40
 */

namespace app\lib\exception;

class AdminException extends BaseException
{
    // public $code = 400;
    public $msg = '账号异常';
    public $solelyCode ='202000';
}