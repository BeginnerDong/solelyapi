<?php

/**

 * Created by 七月.

 * User: 七月

 * Date: 2017/2/15

 * Time: 1:00

 */



namespace app\api\controller\v1;


use think\Controller;

use app\api\controller\v1\weFunc\Project as WxProject;

use app\api\service\beforeModel\Common as BeforeModel;


class Project extends Controller{


    /**
     * 日历提醒功能
     */
    public static function addNotice()
    {

        //校验salt
        $salt = $data['salt'];
        $p_salt = md5('banan');
        if ($salt!=$p_salt) {
            return "salt error!";
        }

        //获取需要提醒的案件信息
        $timeNow = time();
        $modelData = [];
        $modelData['searchItem']['start_time'] = ['<',$timeNow];
        $modelData['searchItem']['end_time'] = ['>',$timeNow];
        $caselist = BeforeModel::CommonGet('Message',$modelData);

        if (count($caselist['data'])>0) {
            
            foreach ($caselist['data'] as $key => $value) {
                
                $case_time = date("Y-m-d H:i",$value['open_time']/1000);
                $message = $value['plaintiff']."的案件将于".$case_time."在".$value['location']."开庭";
                //记录一条站内信
                $modelData = [];
                $modelData['FuncName'] = 'add';
                $modelData['data']['title'] = "案件提醒";
                $modelData['data']['result'] = $message;
                $modelData['data']['user_no'] = $value['user_no'];
                $modelData['data']['user_type'] = $value['user_type'];
                $modelData['data']['thirdapp_id'] = $value['thirdapp_id'];
                $addLog = BeforeModel::CommonSave('Log',$modelData);

                //测试发送模板消息
                $modelData = [];
                $modelData['searchItem']['user_no'] = $value['user_no'];
                $modelData['thirdapp_id'] = $value['thirdapp_id'];
                $send = WxProject::sendMessage($modelData);
            }

        }

    }

}