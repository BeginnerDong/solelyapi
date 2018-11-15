<?php
/**
 * Created by 七月
 * Author: 七月
 * Date: 2017/2/18
 * Time: 13:47
 */

namespace app\lib\exception;


class ArticleException extends BaseException
{
    // public $code = 400;
    public $msg = '文章不存在';
    public $solelyCode = 206000;
}