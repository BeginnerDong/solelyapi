<?php

namespace app\api\controller\v1\func;



use think\Request as Request; 

use think\Controller;

use think\Cache;

use think\Loader;

use app\api\model\Common as CommonModel;

use app\api\controller\BaseController;

use app\api\controller\v1\WxController as WxController;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;


//模板相关

class Wechat extends BaseController{



    protected $beforeActionList = [


    ];


    //发送模板消息

    public static function sendMessage(){

        $data = Request::instance()->param();

        $data = checkSmsAuth($data);

        $data = transformExcel($data);

        $Wechat = new WxController;

        $modelData = [];
        if (isset($data['getBefore'])) {
            $modelData['getBefore'] = $data['getBefore'];
        };
        if (isset($data['searchItem'])) {
            $modelData['searchItem'] = $data['searchItem'];
        };
        $modelData['searchItem']['thirdapp_id'] = $data['thirdapp_id'];
        $modelData['searchItem']['status'] = 1;
        $modelData['getAfter'] =['formIdArray'=>[
            'tableName'=>'WxFormId',
            'middleKey'=>'user_no',
            'key'=>'user_no',
            'condition'=>'=',
            'info'=>['id','form_id']
        ]];
        $user=CommonModel::CommonGet('User',$modelData);

        if(count($user['data'])>0){
            $userdId = [];
            foreach ($user['data'] as $key => $value) {
                if(isset($value['formIdArray']['form_id'])&&isset($value['formIdArray']['id'])){
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
                    $res = $Wechat->sendMessage($post_data,$data['thirdapp_id']);
                    
                    $modelData = [];
                    $modelData['searchItem']['id'] = $value['formIdArray']['id'];
                    $modelData['searchItem']['end_time'] = ['>',time()];
                    $modelData['data']['status'] = -1;
                    $modelData['FuncName'] = 'update';
                    $res=CommonModel::CommonSave('WxFormId',$modelData);
                };
            };
        };

        throw new SuccessMessage([
            'msg'=>'发送成功',
        ]);

    }

}