<?php
/**
 * Created by 董博明
 * Author: 董博明
 * Date: 2018/2/27
 * Time: 16:23
 */

namespace app\lib\exception;


class RemarkException extends BaseException
{
    public $msg = 'art_id参数有误';
    public $solelyCode = 208000;
}