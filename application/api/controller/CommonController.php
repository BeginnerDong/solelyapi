<?php
namespace app\api\controller;
use think\Controller;
use app\api\model\ThirdApp as ThirdAppModel;
use think\Request as Request;
use app\api\service\base\Token as TokenService;
use app\lib\exception\TokenException;

class CommonController extends Controller{


	//平台入口文件
    public function _initialize(){
        //判断项目来源
        //检验thirdapp_id,记录访问次数
        $data=Request::instance()->param();
        if(isset($data['thirdapp_id'])) {
            $result = ThirdAppModel::addViewCount($data['thirdapp_id']);
        }elseif (isset($data['searchItem']['thirdapp_id'])) {
            $result = ThirdAppModel::addViewCount($data['searchItem']['thirdapp_id']);
        }elseif (isset($data['token'])) {
        	$userinfo = TokenService::getUserinfo($data['token']);
        	$result = ThirdAppModel::addViewCount($userinfo['thirdapp_id']);
        }else{
        	throw new TokenException([
        		'msg' => '缺少重要参数thirdapp_id/token',
                'solelyCode' => 200000
            ]);
        }
    }


    /**
     * @param array 接收到的数据
     * @return 检查token参数是否设置
     */
    protected function checkToken()
    {
        $data=Request::instance()->param();
        if (!isset($data['token'])) {
            throw new TokenException();
        }else{
            $userinfo = TokenService::getUserinfo($data['token']);
            if (empty($userinfo)) {
                throw new TokenException();
            }
        }
    }


    /**
     * @param array 接收到的数据
     * @return 检查thirdappID参数是否设置
     */
    protected function checkThirdID()
    {
        $data=Request::instance()->param();
        if (!isset($data['thirdapp_id'])) {
            throw new TokenException([
                'msg'=>'缺少参数ThirdID',
                'solelyCode' => 200002
            ]);
        }
    }


    /**
     * @param array 接收到的数据
     * @return 检查id参数是否设置
     */
    protected function checkID()
    {
        $data=Request::instance()->param();
        if (!isset($data['id'])) {
            throw new TokenException([
                'msg'=>'缺少参数ID',
                'solelyCode'=>200001
            ]);
        }
    }
    
}