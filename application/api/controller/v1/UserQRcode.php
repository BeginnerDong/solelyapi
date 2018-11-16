<?php
/**
 * Created by 董博明.
 * User: 董博明
 * Date: 2018/4/10
 * Time: 18:25
 */

namespace app\api\controller\v1;

use think\Controller;
use app\api\controller\CommonController;
use app\lib\exception\TokenException;
use app\lib\exception\SuccessMessage;
use app\api\model\ThirdApp as ThirdAppModel;
use app\api\model\Member as MemberModel;
use app\api\model\Image as ImageModel;
use app\api\service\Token as TokenService;
use think\Request as Request; 

// 七牛云SDK
use Qiniu\Auth as Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;


/**
 * 用户获取场景二维码
 */
class UserQRcode extends CommonController{

    protected $beforeActionList = [
        'checkToken' => ['only' => 'getCode']
    ];

    public function getCode(){

        $data = Request::instance()->param();
        $userinfo = TokenService::getUserinfo($data['token']);
        $memberinfo = MemberModel::getMemberInfoByUser($userinfo['id']);
        if (empty($memberinfo)) {
            throw new TokenException([
                'msg' => '会员信息不存在',
                'solelyCode' => 214000
            ]);
        }else{
            if ($memberinfo['QRcode']!=='[]') {
               $qrcode = json_decode($memberinfo['QRcode'],true);
               echo json_encode(['msg'=>'获取成功','solely_code'=>100000,'url'=>$qrcode['url']]);
               exit;
            }
        }
        $thirdappinfo = ThirdAppModel::getThirdUserInfo($userinfo['thirdapp_id']);
        $tokenurl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$thirdappinfo['appid'].'&secret='.$thirdappinfo['appsecret'];
        $result = curl_get($tokenurl);
        if ($result) {
            $result = json_decode($result,true);
            $access_token = $result['access_token'];
            $codeurl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
            $getcode = curl_post($codeurl,$data['codeinfo']);
            $codelen = strlen($getcode);
            //判断图片二进制流大小
            if ($codelen<4194304) {
                // 上传到七牛后保存的文件名
                $key = 'qrcode'.substr(md5($userinfo['id']),0,5).date('YmdHis').rand(0, 9999).'.png';
                require_once APP_PATH . '/../vendor/qiniu/autoload.php';
                // 需要填写你的 Access Key 和 Secret Key
                $accessKey = config('qiniu.ACCESSKEY');
                $secretKey = config('qiniu.SECRETKEY');
                // 构建鉴权对象
                $auth = new Auth($accessKey, $secretKey);
                // 要上传的空间
                $bucket = config('qiniu.BUCKET');
                $domain = config('qiniu.DOMAIN');
                //自定义返回值
                $returnBody = '{"key":"$(key)","hash":"$(etag)","fsize":$(fsize),"name":"$(x:name)"}';
                $policy = array(
                    'returnBody' => $returnBody
                );
                $upToken = $auth->uploadToken($bucket,null,3600,$policy,true);//获取上传所需的token
                // 初始化 UploadManager 对象并进行文件的上传
                $uploadMgr = new UploadManager();
                // 调用 UploadManager 的 putFile 方法进行文件的上传
                list($ret, $err) = $uploadMgr->QRputFile($upToken, $key, $getcode);
                if ($err !== null) {
                    throw new TokenException([
                        'msg' => '图片服务器错误：'.$err,
                        'solelyCode' => 204002
                    ]);
                } else {
                    //记录img表
                    $data = array(
                        "thirdapp_id" => $userinfo['thirdapp_id'],
                        "name"        => $ret['key'],
                        "path"        => $domain.$ret['key'],
                        "ownership"   => 2,
                        "size"        => $ret['fsize'],
                        "create_time" => time(),
                    );
                    $res = ImageModel::addImg($data);
                    if ($res == 1) {
                        $upinfo['picstorage'] = $thirdappinfo['picstorage'] + $ret['fsize'];
                        $update = ThirdAppModel::upTuser($userinfo['thirdapp_id'],$upinfo);
                        //更新member信息
                        $qrcodearray = array(
                            "name"=>"qrcode",
                            "url"=>$domain.$ret['key'],
                        );
                        $upmember['QRcode'] = json_encode($qrcodearray);
                        $updatemember = MemberModel::updateMember($memberinfo['id'],$upmember);
                        // 返回图片的完整URL
                        echo json_encode(['msg'=>'获取成功','solely_code'=>100000,'url'=>$domain.$ret['key']]);
                        exit;
                    }else{
                        throw new TokenException([
                            'msg' => '图片信息写入数据库失败',
                            'solelyCode' => 204005
                        ]);
                    }
                }
            }else{
                throw new TokenException([
                    'msg' => '图片大小超过限制',
                    'solelyCode' => 204008
                ]);
            }
        }
    }
}