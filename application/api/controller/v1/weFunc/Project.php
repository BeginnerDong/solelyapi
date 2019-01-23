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

use app\api\controller\v1\weFunc\Message as WxMessage;

use app\api\service\beforeModel\Common as BeforeModel;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;


class Project extends Controller{


  function __construct($data)
  {

  }


	public static function sendMessage($data,$inner=false)
  {

        $modelData = [];
        if (isset($data['getBefore'])) {
            $modelData['getBefore'] = $data['getBefore'];
        };
        if (isset($data['searchItem'])) {
            $modelData['searchItem'] = $data['searchItem'];
        };
        $modelData['searchItem']['thirdapp_id'] = $data['thirdapp_id'];
        $modelData['searchItem']['status'] = 1;
        $modelData['getAfter'] = ['formIdArray'=>[
            'tableName'=>'WxFormId',
            'middleKey'=>'user_no',
            'key'=>'user_no',
            'condition'=>'=',
            'info'=>['id','form_id'],
            'searchItem'=>[
              'end_time'=>['<',time()],
            ],
        ]];
        $user = BeforeModel::CommonGet('User',$modelData);

        if(count($user['data'])>0){
            $userdId = [];
            $result = [];
            foreach ($user['data'] as $key => $value) {

                if(isset($value['formIdArray']['form_id'])&&isset($value['formIdArray']['id'])){

                	$result[$key] = [];
                    array_push($userdId,$value['formIdArray']['id']);
                    $data_arr = array(
                      'keyword1' => array( "value" => date("Y-m-d")),
                      'keyword2' => array( "value" => $data['msg']),
                    );
                    $post_data = array (
                      "touser"           => $value['openid'],
                      // 小程序后台申请到的模板编号
                      "template_id"      => $data['template_id'],
                      // 点击模板消息后跳转到的页面，可以传递参数,如"pages/index/index",
                      "page"             => $data['path'],
                      // 第一步里获取到的 formID
                      "form_id"          => $value['formIdArray']['form_id'],
                      // 数据
                      "data"             => $data_arr,
                      // 需要强调的关键字，会加大居中显示
                      //"emphasis_keyword" => "keyword2.DATA"
                    );
                    // 将数组编码为 JSON
                    $post_data = json_encode($post_data, true);   
                    // 这里的返回值是一个 JSON，可通过 json_decode() 解码成数组
                    $send = WxMessage::sendMessage($post_data,$data['thirdapp_id']);

                    $result[$key]['code'] = $send['errcode'];
                   	$result[$key]['msg'] = $send['errmsg'];

                    if ($send['errcode']!=0) {
                    	continue;
                    }
                    
                    $modelData = [];
                    $modelData['searchItem']['id'] = $value['formIdArray']['id'];
                    $modelData['searchItem']['end_time'] = ['>',time()];
                    $modelData['data']['status'] = -1;
                    $modelData['FuncName'] = 'update';
                    $res = BeforeModel::CommonSave('WxFormId',$modelData);
                    $result[$key]['updateFormID'] = $res;
                };
            };
        };

        if (!$inner) {
        	
        	throw new SuccessMessage([
	            'msg'=>'发送成功',
	            'res'=>$result,
	        ]);

        }else{

        	return $result;

        }
        
    }

}