<?php
/**
 * Created by 董博明
 * Author: 董博明
 * Date: 2018/3/8
 * Time: 19:06
 */

namespace app\lib\exception;


class AddressException extends BaseException
{
    public $msg = '用户地址不存在';
    public $solelyCode = 210000;
}