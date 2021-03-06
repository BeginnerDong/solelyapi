<?php

namespace app\api\model;

use think\Model;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Distribution extends Model
{

    public static function dealAdd($data)
    {   
        $standard = [
            'level'=>'',
            'parent_no'=>'',
            'child_no'=>'',
            'thirdapp_id'=>'',
            'create_time'=>time(),
            'update_time'=>'',
            'delete_time'=>'',
            'status'=>1,
            'child_str'=>''
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
