<?php

namespace app\api\model;

use think\Model;

use app\api\model\Label;
use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Article extends BaseModel
{

    public static function dealAdd($data)
    {   
        $standard = [
            'title'=>'',
            'small_title'=>'',
            'description'=>'',
            'content'=>'',
            'mainImg'=>[],
            'bannerImg'=>[],
            'contactPhone'=>'',
            'keywords'=>'',
            'menu_id'=>'',
            'listorder'=>'',
            'view_count'=>0,
            'thirdapp_id'=>'',
            'passage1'=>'',
            'passage2'=>'',
            'passage_array'=>[],
            'create_time'=>time(),
            'update_time'=>'',
            'delete_time'=>'',
            'status'=>1,
            'user_no'=>'',
            'spu_array'=>[],
            'spu_item'=>[],
            'img_array'=>[],
        ];
        
        $data['data'] = chargeBlank($standard,$data['data']);
        $res = Label::get(['id' => $data['data']['menu_id']]);
        if(!$res){
        	throw new ErrorMessage([
                'msg' => '关联信息有误',
            ]);
    	};      
        return $data;
        
    }

    public static function dealGet($data)
    {   

        
        foreach ($data as $key => $value) {

            $label = array();
            array_push($label,$value['menu_id']);
            
            $label = array_merge($label,$value['spu_array'],$value['spu_item']);
            $map['id'] = ['in',$label];
            $res = resDeal((new Label())->where($map)->select());
            if(count($res)>0){
                $res = clist_to_tree($res);
                $res = changeIndexArray('id',$res);
                $data[$key]['label'] = $res;
            }else{
                $res = Article::destroy($value['id']);
                unset($data[$key]);
            }
            

        };

        
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
