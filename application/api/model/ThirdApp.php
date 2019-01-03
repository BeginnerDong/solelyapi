<?php
namespace app\api\model;
use think\Model;
use think\Db;
use think\Cache;

use app\api\model\User;

use app\lib\exception\ErrorMessage;

class ThirdApp extends BaseModel{
    

    public static function dealAdd($data)
    {   

        $standard = ['appid'=>'','appsecret'=>'','app_description'=>'','name'=>'','codeName'=>'','distribution'=>'','distributionRule'=>'','custom_rule'=>'','phone'=>'','mainImg'=>[],'smsKey_ali'=>'','smsSecret_ali'=>'','smsID_tencet'=>'','smsKey_tencet'=>'','scope'=>'','scope_description'=>'','app_type'=>'','mchid'=>'','wxkey'=>'','wx_token'=>'','wxgh_id'=>'','wx_appid'=>'','wx_appsecret'=>'','encodingaeskey'=>'','access_token'=>'','access_token_expire'=>'','aestype'=>'','picstandard'=>'','picstorage'=>'','create_time'=>time(),'update_time'=>'','delete_time'=>'','invalid_time'=>'','view_count'=>'','status'=>1,'child_array'=>[],'user_no'=>''];

        $data['data'] = chargeBlank($standard,$data['data']);
        return $data;

    }

    public static function dealGet($data)
    {   

        return $data;
        
    }

    public static function dealUpdate($data)
    {   

        if(isset($data['data'])&&isset($data['data']['status'])){
            $ThirdApp = (new ThirdApp())->where($data['searchItem'])->select();
            foreach ($ThirdApp as $key => $value) {
                $relationRes = (new User())->save(
                    ['status'  => $data['data']['status']],
                    ['thirdapp_id' => $ThirdApp[$key]['id']]
                );
            };
        };
        return $data;
        
    }

    public static function dealRealDelete($data)
    {   
        return $data;
    }





















    public static function ThirdAppGet($data){

        $model = new ThirdApp;
        $sqlStr = preModelStr($data);
        $sqlStr = $sqlStr."->select();";
        $res = eval($sqlStr);
        return $res->toArray();
        
    }





    //关联admin表
    public function admin(){
        return $this->hasMany('admin', 'thirdapp_id', 'id');
    }

    //关联user表
    public function user(){
        return $this->hasMany('user', 'thirdapp_id', 'id');
    }

    //获取当前商户的所有admin用户列表
    public static function getAdminThirdUser($data,$token){
        $map['status']=1;
        $map=$data['map'];
        $userinfo=Cache::get('info'.$token);
        $map['thirdapp_id']=$userinfo->thirdapp_id;
        $user=new Admin();
        $list=$user->where($map)->select();
        if(!$list->isEmpty()){
            foreach($list as $k=>$v){
                $list[$k]['img']=json_decode($v['img']);
            }
        }
        return $list;
    }
    
    //获取当前商户的所有admin用户列表(带分页)
    public static function getAdminThirdUserToPaginate($data,$token){
        $map['status']=1;
        $map=$data['map'];
        $user=new Admin();
        $userinfo=Cache::get('info'.$token);
        $map['thirdapp_id']=$userinfo->thirdapp_id;
        $list=$user->where($map)->order('create_time','desc')->paginate($data['pagesize']);
        if(!$list->isEmpty()){
            $list=$list->toArray();
            foreach($list['data'] as $k=>$v){
                $list['data'][$k]['headImg']=json_decode($v['headImg']);
            }
        }
        return $list;
    }

    //获取当前商户的所有user用户信息
    public static function getUserThirdUser($data){
        $map['status']=1;
        $map['thirdapp_id']=$data['id'];
        $user=new User();
        $list=$user->where($map)->select();
        if(!$list->isEmpty()){
            $list=$list->toArray();  
            foreach($list as $k=>$v){
                $list[$k]['headimgurl']=json_decode($v['headimgurl']);
            }
        }
        return $list;
    }

    //获取列表
    public static function getAllTuser($data){
        $ThirdApp=new ThirdApp();
        $map=$data['map'];
        $map['status']=1;
        $list=$ThirdApp->where($map)->order('create_time','desc')->select();
        $list=$list->toArray();
        foreach($list as $key=>$value){
            $list[$key]['headImg']=json_decode($value['headImg'],true);
        }
        return $list;
    }

    //获取列表(带分页)
    public static function getAllTuserToPaginate($data){
        $ThirdApp=new ThirdApp();
        $map=$data['map'];
        $map['status']=1;
        $list=$ThirdApp->where($map)->order('create_time','desc')->paginate($data['pagesize']);
        $list=$list->toArray();
        foreach($list['data'] as $key=>$value){
            $list['data'][$key]['headImg']=json_decode($value['headImg'],true);
        }
        return $list;
    }

    //添加thirduser
    public static function addTuser($data){
        $user = new ThirdApp();
        $res = $user->allowField(true)->save($data);
        return $user->id;
    }

    //修改
    public static function upTuser($id,$data){
        $user = new ThirdApp(); 
        $data['update_time'] = time();
        $res=$user->allowField(true)->save($data,['id' => $id]);      
        return $res;
    }

    //获取指定商户信息
    public static function getThirdUserInfo($id){
        $user = new ThirdApp();                                 
        $res = $user->where('id','=',$id)->find();
        $res['headImg'] = json_decode($res['headImg'],true);     
        return $res;
    }

    //软删除
    public static function delTuser($id){
        $user = new ThirdApp();
        $info['delete_time'] = time();
        $info['status'] = -1;              
        $res = $user->allowField(true)->save($info,['id'=>$id]);                  
        return $res;    
    }

    //物理删除
    public static function TruedelTuser($data){
        $thirdApp=Db::table('third_app')->where('id',$data['id'])->delete();
        $admin=Db::table('admin')->where('thirdapp_id',$data['id'])->delete();
        $article=Db::table('article')->where('thirdapp_id',$data['id'])->delete();
        $articleContent=Db::table('article_content')->where('thirdapp_id',$data['id'])->delete();
        $category=Db::table('category')->where('thirdapp_id',$data['id'])->delete();
        $product=Db::table('product')->where('thirdapp_id',$data['id'])->delete();
        $user=Db::table('user')->where('thirdapp_id',$data['id'])->delete();
        $userAddress=Db::table('user_address')->where('thirdapp_id',$data['id'])->delete();
        if($thirdApp||$admin||$article||$articleContent||$category||$product||$user||$userAddress){
            return 1;
        }else{
            return 0;
        }
    }

    //访问量记录
    public static function addViewCount($id)
    {
        $viewadd = Db::table('third_app')->where('id',$id)->setInc('view_count');
        return $viewadd;
    }
}
