<?php
/**
 * Created by 七月
 * Author: 七月
 * Date: 2017/2/18
 * Time: 13:47
 */

namespace app\lib\exception;


class MenuException extends BaseException
{
    // public $code = 400;
    public $msg = '菜单异常';
    public $solelyCode = 203000;
}