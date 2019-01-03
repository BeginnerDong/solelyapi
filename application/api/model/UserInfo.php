<?php

namespace app\api\model;


use think\Model;

use app\api\model\User;
use app\api\model\FlowLog;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

class UserInfo extends Model
{

    public static function dealAdd($data)
    {   
        
        $standard = ['mainImg'=>[],'name'=>'','phone'=>'','gender'=>'','email'=>'','address'=>'','level'=>'','deadline'=>'','passage_array'=>[],'passage1'=>'','signin_time'=>'','thirdapp_id'=>'','create_time'=>time(),'update_time'=>'','delete_time'=>'','user_no'=>'','user_type'=>'','idCard'=>'','score_ratio'=>1,'score'=>0,'balance'=>0];

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

        $data['data'] = chargeBlank($standard,$data['data']);
        $res = User::get(['user_no' => $data['data']['user_no']]);

        if($res){
        	$res = UserInfo::get(['user_no' => $data['data']['user_no']]);
            if($res){
                throw new ErrorMessage([
                    'msg' => '已经存在UserInfo',
                ]); 
            };
        }else{
        	throw new ErrorMessage([
                'msg' => '关联信息有误',
            ]);
    	};
        $map = [
            'user_no'=>$data['data']['user_no'],
            'status'=>1,
            'type'=>2
        ];
        $data['data']['balacne'] = FlowLog::where($map)->sum('count');

        $map = [
            'user_no'=>$data['data']['user_no'],
            'status'=>1,
            'type'=>3
        ];
        $data['data']['score'] = FlowLog::where($map)->sum('count');
        return $data;
    }

    public static function dealGet($data)
    {    
        return $data; 
    }

    public static function dealUpdate($data)
    {   
    	return $data;  
    }

    public static function dealRealDelete($data)
    {   
    	return $data;   
    }

}


