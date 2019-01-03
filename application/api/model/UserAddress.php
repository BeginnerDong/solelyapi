<?php

namespace app\api\model;


use think\Model;
use app\api\model\User;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

class UserAddress extends BaseModel
{

    public static function dealAdd($data)
    {   
        
        $standard = ['name'=>'','phone'=>'','province'=>'','city'=>'','country'=>'','detail'=>'','longitude'=>'','latitude'=>'','user_no'=>'','thirdapp_id'=>'','isdefault'=>'','create_time'=>time(),'update_time'=>'','delete_time'=>'','status'=>1,'user_type'=>''];


        if(isset($data['data']['user_no'])){
            $res = User::get(['user_no' => $data['data']['user_no']]);
            if($res){
                $data['data']['user_type'] = $res['user_type'];
            }else{
                throw new ErrorMessage([
                    'msg' => '关联user信息有误',
                ]);
            };
        };
        if(isset($data['data'])&&isset($data['data']['isdefault'])&&$data['data']['isdefault']==1){
            $res = UserAddress::where('user_no', $data['searchItem']['user_no'])->update(['isdefault' => 0]);
        };

        $data['data'] = chargeBlank($standard,$data['data']);
        return $data;
        

    }

    public static function dealGet($data)
    {   

        
        return $data;
        
    }

    public static function dealUpdate($data)
    {   
        if(isset($data['data'])&&isset($data['data']['isdefault'])&&$data['data']['isdefault']==1){
            $res = UserAddress::where('user_no', $data['searchItem']['user_no'])->update(['isdefault' => 0]);
        };
    	return $data;
        
    }

    public static function dealRealDelete($data)
    {   

    	return $data;
        
    }


}