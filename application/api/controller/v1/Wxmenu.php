<?php
namespace app\api\controller\v1;
use think\Request as Request;
use think\Controller;
use app\api\model\Wxmenu as WxmenuModel;
use app\api\service\Wxmenu as WxmenuService;
use app\api\controller\BaseController;
use app\lib\exception\SuccessMessage;
use app\lib\exception\TokenException;
use think\Cache;

class Wxmenu extends BaseController{

    protected $beforeActionList = [
        'checkID' => ['only' => 'editMenu,delMenu,getInfo']
    ];

    //新增菜单
    public function addMenu(){
        $data = Request::instance()->param();
        $menuservice = new WxmenuService();
        $info = $menuservice->addinfo($data);
        $res = WxmenuModel::addMenu($info); 
        if($res==1){
            throw new SuccessMessage([
                'msg'=>'添加成功',
            ]);
        }else{
            throw new TokenException([
                'msg' => '添加菜单失败',
                'solelyCode'=>203000
            ]);
        }     
    }

    //修改菜单
    public function editMenu(){
        $data = Request::instance()->param();
        $menuservice = new WxmenuService();
        $checkinfo = $menuservice->checkinfo($data['token'],$data['id']);
        switch ($checkinfo) {
            case 1:
                break;
            case -1:
                throw new TokenException([
                    'msg' => '菜单不属于该商户',
                    'solelyCode' => 203001
                ]);
            case -2:
                throw new TokenException([
                    'msg' => '菜单已删除',
                    'solelyCode' => 203002
                ]);
            case -3:
                throw new TokenException([
                    'msg' => '菜单不存在',
                    'solelyCode' => 203007
                ]);
        }
        $res = WxmenuModel::updateMenu($data['id'],$data);
        if($res==1){
            throw new SuccessMessage([
                'msg'=>'修改菜单成功',
            ]);
        }else{
            throw new TokenException([
                'msg' => '修改失败',
                'solelyCode' => 203003
            ]);
        }
    }

    //软删除
    public function delMenu(){
        $data = Request::instance()->param();
        $menuservice = new WxmenuService();
        $checkinfo = $menuservice->checkinfo($data['token'],$data['id']);
        switch ($checkinfo) {
            case 1:
                break;
            case -1:
                throw new TokenException([
                'msg' => '菜单不属于该商户',
                'solelyCode' => 203001
            ]);
            case -2:
                throw new TokenException([
                    'msg' => '菜单已删除',
                    'solelyCode' => 203002
                ]);
            case -3:
                throw new TokenException([
                    'msg' => '菜单不存在',
                    'solelyCode' => 203007
                ]);
        }
        $res = WxmenuModel::delMenu($data['id']);
        if($res==1){
            throw new SuccessMessage([
                'msg'=>'删除菜单成功',
            ]);
        }else{
            throw new TokenException([
                'msg' => '菜单删除失败',
                'solelyCode' => 203004
            ]);
        }
    }

    //获取菜单层级树
    public function getTree(){
        $data = Request::instance()->param();
        $list = preAll2($data);
        $tree = WxmenuModel::getMenuTree($list);
        return $tree;
    }

    //获取菜单列表
    public function getList(){
        $data = Request::instance()->param();
        $menuservice = new WxmenuService();
        $checkData = checkData($data);
        if($checkData['code']!==2){
            return $checkData;
        }
        $info = preAll($data);
        // $userinfo = Cache::get('info'.$data['token']);
        // $info['map']['thirdapp_id'] = $userinfo['thirdapp_id'];
        if(isset($info['pagesize'])&&isset($info['currentPage'])){
            $list = WxmenuModel::getMenuListToPaginate($info,$data['token']);
        }else{
            $list = WxmenuModel::getMenuList($info,$data['token']);
        }
        if($list){
            return $list;
        }else{
            throw new TokenException([
                'msg'=>'获取列表失败',
                'solelyCode'=>203005
            ]);
        }
    }

    //获取菜单信息
    public function getInfo(){
        $data = Request::instance()->param();
        $userinfo = Cache::get('info'.$data['token']);
        $res = WxmenuModel::getMenuInfo($data['id']);
        if($res){
            if ($res['status']==-1) {
                throw new TokenException([
                    'msg' => '菜单已删除',
                    'solelyCode' => 203002
                ]);
            }
            if ($res['thirdapp_id']!=$userinfo['thirdapp_id']) {
                throw new TokenException([
                    'msg' => '菜单不属于该商户',
                    'solelyCode' => 203001
                ]);
            }
            return $res;
        }else{
            throw new TokenException([
                'msg' => '菜单不存在',
                'solelyCode' => 203007
            ]);
        }
    }
}