<?php
namespace app\api\controller\v1\base;

use think\Request as Request; 
use think\Controller;
use think\Cache;

use app\api\controller\BaseController;
use app\api\service\base\Token as TokenService;
use app\api\service\base\Admin as AdminService;
use app\api\model\ThirdApp as ThirdAppModel;
use app\api\model\Admin as AdminModel;

use app\api\validate\AdminValidate;
use app\api\validate\AdminUptValidate;
use app\lib\exception\AdminException;
use app\lib\exception\SuccessMessage;


//模板相关
class Admin extends BaseController{


    protected $beforeActionList = [
        'checkID' => ['only' => 'UpdateAdmin,DelAdmin,getInfo'],
        'checkPrimary' => ['only' => 'AddAdmin,UpdateAdmin,DelAdmin,getAllAdmin,getAdminInfo']
    ];


    //主方法
    public static function admin(){
        $data = Request::instance()->param();
        (new AdminValidate())->goCheck($data);
        return AdminService::$data['serviceName']($data);
    }


    //添加
    public static function AddAdmin()
    {
        $data = Request::instance()->param(); 
        $validate = new AdminValidate();
        $validate->goCheck();
        //判断商户名，appid ,appsecret是否重复
        $result = AdminService::checkIsRepeat($data);
        if($result==-3){
            throw new AdminException([
                'msg' => '此名称已经注册过啦',
                'solelyCode' => 202001
            ]);
        }
        $res = AdminModel::addAdmin($data);
        if($res==-1){
            throw new AdminException([
                'msg' => 'primary_scope参数有误',
                'solelyCode' => 202002
            ]);
        }else{
            throw new SuccessMessage([
                'msg'=>'添加成功'
            ]);
        }
    }

    //修改
    public static function UpdateAdmin()
    {
        $data = Request::instance()->param();
        $validate = new AdminUptValidate();
        $validate->goCheck();
        //判断商户名，appid ,appsecret是否重复
        $result = AdminService::checkIsRepeat($data);
        if($result==-3){
            throw new AdminException([
                'msg' => '此名称已经注册过啦',
                'solelyCode' => 202001
            ]);
        }
        $check = AdminService::checkinfo($data['token'],$data['id']);
        switch ($check) {
            case 1:
                break;
            case -1:
                throw new AdminException([
                    'msg' => '此用户不属于该商户',
                    'solelyCode' => 202005
                ]);
            case -2:
                throw new AdminException([
                    'msg' => '该用户已被删除',
                    'solelyCode' => 202004
                ]);
            case -3:
                throw new AdminException([
                    'msg' => '用户信息不存在',
                    'solelyCode' => 202011
                ]);
            case -4:
                throw new AdminException([
                    'msg' => '无权限修改',
                    'solelyCode' => 202003
                ]);
        }
        $res = AdminModel::upAdmin($data);
        if($res==1){
            throw new SuccessMessage([
                'msg'=>'修改成功'
            ]);
        }else{
            throw new AdminException([
                'msg' => '修改失败',
                'solelyCode' => 202013
            ]);
        }
    }

    //软删除
    public static function DelAdmin()
    {
        $data = Request::instance()->param();
        $validate = new AdminUptValidate();
        $validate->goCheck();
        $check = AdminService::checkinfo($data['token'],$data['id']);
        switch ($check) {
            case 1:
                break;
            case -1:
                throw new AdminException([
                    'msg' => '此用户不属于该商户',
                    'solelyCode' => 202005
                ]);
            case -2:
                throw new AdminException([
                    'msg' => '该用户已被删除',
                    'solelyCode' => 202004
                ]);
            case -3:
                throw new AdminException([
                    'msg' => '用户信息不存在',
                    'solelyCode' => 202011
                ]);
            case -4:
                throw new AdminException([
                    'msg' => '无权限修改',
                    'solelyCode' => 202003
                ]);
        }
        $res = AdminModel::delAdmin($data['id']);
        if($res==1){
            throw new SuccessMessage([
                'msg'=>'删除成功 '
            ]);
        }else{
            throw new AdminException([
                'msg' => '删除失败',
                'solelyCode' => 202012
            ]); 
        }
    }

    //获取列表
    public function getAllAdmin(Request $Request)
    {
        $data = $Request->param();
        $checkData = checkData($data);
        if($checkData['code']!==2){
            return $checkData;
        }
        $info = preAll($data);
        $userinfo = Cache::get('info'.$data['token']);
        $info['map']['thirdapp_id'] = $userinfo['thirdapp_id'];
        if(isset($info['pagesize'])&&isset($info['currentPage'])){
            $list = AdminModel::getAllAdminToPaginate($info);
        }else{
            $list = AdminModel::getAllAdmin($info);
        };
        //项目特殊参数判断是否有设置返回
        $userinfo = Cache::get('info'.$data['token']);
        $thirdappinfo = ThirdappModel::getThirdUserInfo($userinfo['thirdapp_id']);
        if ($thirdappinfo['distribution']!="[]") {
            $list['distributionRule'] = json_decode($thirdappinfo['distributionRule'],true);
        }
        if ($thirdappinfo['custom_rule']!="[]") {
            $list['customRule'] = json_decode($thirdappinfo['custom_rule'],true);
            if (isset($list['customRule']['discount'])) {
                $list['customRule']['discount'] = $list['customRule']['discount']/10;
            }
        }
        return $list;
    }

    //admin用户登录
    public static function LoginByAdmin()
    {
        $data = Request::instance()->param();
        $validate = new AdminValidate();
        $validate->goCheck();
        $res = AdminModel::LoginByAdmin($data);
        switch($res){
            case '-4':
                throw new AdminException([
                    'msg' =>'该商户被禁用',
                    'solelyCode'=>202000
                ]);
                break;
            case '-3':
                throw new AdminException([
                    'msg' =>'时间更新失败，登录失败',
                    'solelyCode'=>202007
                ]);
                break;
            case '-2':
            throw new AdminException([
                    'msg' =>'该用户已被禁用',
                    'solelyCode'=>202008
                    ]);
                break;
            case '-1':
                throw new AdminException([
                    'msg' =>'密码不正确，登录失败',
                    'solelyCode'=>202009
                ]);
                break;
            case '0':
                throw new AdminException([
                    'msg' =>'用户名不正确，登录失败',
                    'solelyCode'=>202010
                ]);
                break;
            default:
                $time = time()+31536000;
                echo json_encode(['msg'=>'登录成功','solely_code'=>100000,'token'=>$res['token'],'userInfo'=>$res['info'],'invalid_time'=>$time]);
                break;
        }
    }

    //获取指定admin用户信息
    public static function getAdminInfo()
    {
        $data = Request::instance()->param();
        $validate = new AdminUptValidate();
        $validate->goCheck();
        $res = AdminModel::getAdminInfo($data['id']);
        if($res){
            if ($res['status']==-1) {
            	throw new AdminException([
	                'msg' =>'用户已被删除',
	                'solelyCode'=>202004
	            ]);
            }
            return $res;
        }else{
            throw new AdminException([
                'msg' =>'用户信息不存在',
                'solelyCode'=>202011
            ]);
        }
    }

    //admin用户退出
    public static function loginOut()
    {
        $data = Request::instance()->param();
        $res = AdminModel::loginOut($data);
        if($res==1){
            echo json_encode(['msg'=>'登出成功','solely_code'=>$res]);
        }else{
            throw new SuccessMessage([
                'msg'=>'登出成功 '
            ]);
        }
    }

    //修改密码
    public static function updatePassWord()
    {
        $data = Request::instance()->param();
        $validate = new AdminUptValidate();
        $validate->goCheck();
        $res = AdminModel::updatePassWord($data);
        switch($res){
            case '-6':
                throw new AdminException([
                    'msg' =>'id参数有误，您没有操作此用户权限。',
                    'solelyCode'=>202012
                ]);
                break;
            case '-5':
                throw new AdminException([
                    'msg' =>'您没有操作此用户权限。',
                    'solelyCode'=>202013
                ]);
                break;
            case '-4':
                throw new AdminException([
                    'msg' =>'此用户不属于此商户',
                    'solelyCode'=>202005
                ]);
                break;
            case '-3':
                throw new AdminException([
                    'msg' =>'该用户已被删除',
                    'solelyCode'=>202004
                ]);
                break;
            case '-2':
                throw new AdminException([
                    'msg' =>'用户信息不存在',
                    'solelyCode'=>202011
                ]);
                break;
            case '-1':
                throw new AdminException([
                    'msg'=>'必须设置新密码',
                    'solelyCode'=>202014
                ]);
                break;
            default:
                throw new SuccessMessage([
                    'msg'=>'修改成功'
                ]);
                break;
        }
    }

    //获取自己的信息
    public function getMyInfo()
    {
    	$data = Request::instance()->param();
    	$userinfo = Cache::get('info'.$data['token']);
        if($userinfo){
            if ($userinfo ['status']==-1) {
            	throw new AdminException([
	                'msg' =>'用户已被删除',
	                'solelyCode'=>202004
	            ]);
            }
            return $userinfo;
        }else{
            throw new AdminException([
                'msg' =>'用户信息不存在',
                'solelyCode'=>202011
            ]);
        }
    }

    //修改自己的信息
    public function updateMyInfo()
    {
    	$data = Request::instance()->param();
    	$userinfo = Cache::get('info'.$data['token']);
    	$data['id'] = $userinfo['id'];
    	$result = AdminService::checkIsRepeat($data);
        if($result==-3){
            throw new AdminException([
                'msg' => '此名称已经注册过啦',
                'solelyCode' => 202001
            ]);
        }
        $res = AdminModel::upAdmin($data);
        if($res==1){
            throw new SuccessMessage([
                'msg'=>'修改成功'
            ]);
        }else{
            throw new AdminException([
                'msg' => '修改失败',
                'solelyCode' => 202013
            ]);
        }

    }

    //项目续期
    public function RenewTime()
    {
        $data = Request::instance()->param();
        //todo...
    }
}