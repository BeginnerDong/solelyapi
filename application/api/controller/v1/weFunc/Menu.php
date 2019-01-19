<?php
/**
 * Created by 董博明.
 * Author: 董博明
 * Date: 2018/4/9
 * Time: 19:23
 */

namespace app\api\controller\v1\weFunc;
use think\Controller;
use think\Db;
use think\Request as Request;
use think\Loader;

use app\api\controller\v1\weFunc\Base as WxBase;

use app\api\service\beforeModel\Common as BeforeModel;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Menu extends Controller{


	function __construct($data)
	{

	}


	/**
	 * 接收参数：
	 * thirdapp_id
	 */
	public static function pushMenu($thirdapp_id)
	{
		$data = self::getMenudata();

		foreach ( $data as $k => $d ) {
			if ($d ['parentid'] != 0)
				continue;
			$tree  [$d ['id']] = self::dealMenudata( $d );
			unset ( $data [$k] );
		}
		foreach ( $data as $k => $d ) {
			$tree  [$d ['parentid']] ['sub_button'] [] = self::dealMenudata( $d );
			unset ( $data [$k] );
		}
		$menudata = array ();
		foreach ( $tree  as $k => $d ) {
			$menudata  [] = $d;
		}

		$data = Request::instance()->param();

		$modelData = [];
        $modelData['searchItem'] = [
            'id'=>$thirdapp_id
        ];
        $thirdInfo = BeforeModel::CommonGet('ThirdApp',$modelData);

		if(count($thirdInfo['data'])>0){

			$thirdInfo = $thirdInfo['data'][0];

			if($thirdInfo['access_token']&&$thirdInfo['access_token_expire']>time()){

				$access_token = $thirdInfo['access_token'];

			}else{

				$thirdInfo['thirdapp_id'] = $thirdInfo['id'];
				$access_token = WxBase::getAccessToken($thirdInfo);

			}; 

		}else{

			throw new ErrorMessage([
	            'msg'=>'关联项目不存在',
	        ]);

		};

		$delurl = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$access_token;
		$pushurl = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
		$button = array('button' => $menudata);
		$delmenu = curl_get($delurl);

		if ($delmenu) {
			$delmenu = json_decode($delmenu,true);
			// return $delmenu['errmsg'];
		}else{
			return false;
		}

		$createmenu = WxBase::curl_wxpost($pushurl,$button);
		if ($createmenu) {
			$createmenu = json_decode($createmenu,true);
			// return $createmenu['errmsg'];
			echo json_encode(['msg'=>'更新菜单成功','solely_code'=>100000]);
		}else{
			return false;
		}
	}


	public function getMenudata()
	{
		$list = Db::query('select * from wxmenu where status=1 order by listorder desc');
		// 取一级菜单
		foreach ( $list as $k => $vo ) {
			if ($vo ['parentid'] != 0) 
				continue;
			$one_arr[$vo['id']] = $vo;
			unset ( $list [$k] );
		}
		foreach ( $one_arr as $p ) {
			$data [] = $p;
			$two_arr = array ();
			foreach ( $list as $key => $l ) {
				if ($l ['parentid'] != $p ['id'])
					continue;
				$two_arr [] = $l;
				unset ( $list [$key] );
			}
			$data = array_merge($data,$two_arr);
		}
		return $data;
	}


	public function dealMenudata($d)
	{
		if ($d['parentid']==0&&empty($d['wmtype'])) {
			$res ['name'] =  $d ['name'] ;
		}else{
			switch ($d['wmtype']) {
				case 'view':
					$res ['name'] =  $d ['name'] ;
					$res ['type'] = 'view';
					$res ['url'] =  $d ['url'] ;
					break;
				case 'click':
					$res ['name'] =  $d ['name'] ;
					$res ['type'] = 'click';
					$res ['key'] =  $d ['key'] ;
					break;
				case 'miniprogram':
					$res ['name'] =  $d ['name'] ;
					$res ['type'] = 'miniprogram';
					$res ['url'] =  $d ['url'] ;
					$res ['appid'] =  $d ['appid'] ;
					$res ['pagepath'] =  $d ['pagepath'] ;
					break;
				default:
					break;
			}
		}
		return $res;
	}
}