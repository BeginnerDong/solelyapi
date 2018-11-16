<?php
/**
 * Created by wsgt
 */
namespace app\api\validate;
class CommonValidate extends BaseValidate{
	//字段验证规则
    protected $rule = [
        'name'=>'require|isNotEmpty',
        'login_name'=>'require|isNotEmpty',
        'id'=>'require|isNotEmpty|number',
        'password'=>'require|isNotEmpty',
        'token'=>'require|isNotEmpty',
        'order_no'=>'require|isNotEmpty',
        'thirdapp_id'=>'require|isNotEmpty|isPositiveInteger',
        
    ];

    protected $scene = [

    	'one'  =>  ['token'],
        'two'  =>  ['id','token'],
        'three'  =>  ['login_name','password'], 
        'four'  =>  ['login_name','password','token'],
        'five'  =>  ['name','token'],
        'six'  =>  ['order_no','token'],
        
    ];

}
