<?php

namespace app\api\model;
use think\Model;
use app\api\model\UserInfo;
use app\api\model\UserAddress;
use app\api\model\FlowLog;
use app\api\model\Distribution;

use app\lib\exception\ErrorMessage;


class User extends Model{

    public static function dealAdd($data)
    {   
        
        $standard = ['login_name'=>'','password'=>'','wx_mainImg'=>'','create_time'=>time(),'update_time'=>'','delete_time'=>'','lastlogintime'=>'','thirdapp_id'=>'','primary_scope'=>10,'scope'=>'','status'=>1,'user_type'=>0,'behavior'=>'','user_no'=>'','openid'=>'','parent_no'=>'','child_array'=>'','nickname'=>'','passage1'=>'','headImgUrl'=>''];
        $data['data'] = chargeBlank($standard,$data['data']);
        return $data;
        
    }

    public static function dealGet($data)
    {   

        
        $user_no = [];
        foreach ($data as $key => $value) {
            array_push($user_no,$value['user_no']);
        };

        $info = resDeal((new UserInfo())->where('user_no','in',$user_no)->select());
        $address = resDeal((new UserAddress())->where('user_no','in',$user_no)->select());
        $distriParent = resDeal((new Distribution())->where('child_no','in',$user_no)->select());
        $info = changeIndexArray('user_no',$info);
        $address = changeIndexArray('user_no',$address);
        $distriParent = changeIndexArray('child_no',$distriParent);

        foreach ($data as $key => $value) {

            if(isset($info[$value['user_no']])){
                $data[$key]['info'] = $info[$value['user_no']];
            }else{
                $data[$key]['info'] = '';
            };

            if(isset($address[$value['user_no']])){
                $data[$key]['address'] = $address[$value['user_no']];
            }else{
                $data[$key]['address'] = [];
            };

            if(isset($distriParent[$value['user_no']])){
                $data[$key]['distriParent'] = $distriParent[$value['user_no']]['parent_no'];
            }else{
                $data[$key]['distriParent'] = '';
            };

        };
        return $data;
        
    }

    public static function dealUpdate($data){  

        if(array_key_exists("user_no",$data['data'])&&empty($data['data']['user_no'])){
            throw new ErrorMessage([
                'msg' => '关键信息不得为空',
            ]);
        };
        if(isset($data['data'])&&isset($data['data']['status'])){

            $User = (new User())->where($data['searchItem'])->select();

            foreach ($User as $key => $value) {

                //更新userInfo
                $upInfo = UserInfo::where('user_no', $value['user_no'])->update(['status' => $data['data']['status']]);

                //更新address
                $UserAddress = (new UserAddress())->where(['user_no'  => $User[$key]['user_no']])->select();

                foreach ($UserAddress as $a_key => $a_value) {

                    $upAddress = UserAddress::where('id', $a_value['id'])->update(['status' => $data['data']['status']]);

                };

            };
            
        };
    }

    public static function dealRealDelete($data)
    {   

        $UserInfo = (new UserInfo())->where($data['searchItem'])->select();
        foreach ($UserInfo as $key => $value) {
            OrderItem::destroy(['order_no' => $UserInfo[$key]['order_no']]);
        };

        $UserAddress = (new UserAddress())->where($data['searchItem'])->select();
        foreach ($UserAddress as $key => $value) {
            OrderItem::destroy(['order_no' => $UserAddress[$key]['order_no']]);
        };
        
    }


    
}
