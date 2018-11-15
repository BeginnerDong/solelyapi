<?php
namespace app\lib\exception;
class MissException extends BaseException{
    public $code = 404;
    public $msg = '我去，你请求的路由信息不存在';
    public $solelyCode = 10001;
}