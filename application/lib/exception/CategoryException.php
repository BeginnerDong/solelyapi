<?php
/**
 * Created by 七月
 * Author: 七月
 * Date: 2017/2/18
 * Time: 13:47
 */

namespace app\lib\exception;


class CategoryException extends BaseException
{
    public $msg = '指定分类不存在';
    public $solelyCode = 212000;
}