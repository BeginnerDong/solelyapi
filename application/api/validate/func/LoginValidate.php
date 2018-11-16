<?php
/**
 * Created by wsgt
 */
namespace app\api\validate\func;
use app\api\validate\BaseValidate;

class LoginValidate extends BaseValidate{
	//字段验证规则
    protected $rule = [
        'login_name'=>'require|isNotEmpty',
        'password'=>'require|isNotEmpty',
    ];

    protected $scene = [
    	
        'login'  =>  ['login_name','password'], 
        
    ];

}
