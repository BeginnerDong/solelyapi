<?php

namespace app\api\model;


use think\Model;

class WxTemplate extends BaseModel
{

    public static function dealAdd($data)
    {   
        $standard = [
            'name'=>'',
            'template_no'=>'',
            'thirdapp_id'=>'',
            'create_time'=>time(),
            'update_time'=>'',
            'delete_time'=>'',
            'status'=>1,
            'user_no'=>'',
            'user_type'=>'',
        ];
        
        $data['data'] = chargeBlank($standard,$data['data']);
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
