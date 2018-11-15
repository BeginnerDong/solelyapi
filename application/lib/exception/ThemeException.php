<?php
/**
 * Created by 董博明
 * Author: 董博明
 * Date: 2017/3/13
 * Time: 21:01
 */

namespace app\lib\exception;


class ThemeException extends BaseException
{
    public $msg = '指定主题不存在';
    public $solelyCode = 213000;
}