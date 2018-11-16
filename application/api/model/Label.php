<?php

namespace app\api\model;


use think\Model;

class Label extends BaseModel
{

    public static function dealAdd($data)
    {   
        $standard = ['title'=>'','description'=>'','parentid'=>'','listorder'=>'','type'=>'','bannerImg'=>[],'thirdapp_id'=>'','create_time'=>time(),'update_time'=>'','delete_time'=>'','status'=>1,'mainImg'=>[],'user_no'=>'','passage1'=>'','url'=>''];
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
