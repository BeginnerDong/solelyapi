<?php
/**
 * Created by 七月.
 * User: 七月
 * Date: 2017/2/14 我去，情人节，886214
 * Time: 1:03
 */

namespace app\lib\exception;

/**
 * token验证失败时抛出此异常 
 */
class TokenException extends BaseException
{
    public $msg = 'Token已过期或无效Token';
    public $solelyCode = 200000;
}