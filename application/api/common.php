<?php

/**
 * @param string $url post请求地址
 * @param array $params
 * @return mixed
 */
use think\Cache;
use app\lib\exception\TokenException;
use app\api\model\User;
use app\api\model\Distribution;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

function curl_post($url, array $params = array())
{
    $data_string = json_encode($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json'
        )
    );
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);
}

function curl_post_raw($url, $rawData)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: text'
        )
    );
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);
}

/**
 * @param string $url get请求地址
 * @param int $httpCode 返回状态码
 * @return mixed
 */
function curl_get($url, &$httpCode = 0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //不做证书校验,部署在linux环境下请改为true
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,10);
    $file_contents=curl_exec($ch);
    $httpCode=curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $file_contents;
}


//xml格式转化成数组
function xml2array($xml){
    $p=xml_parser_create();
    xml_parse_into_struct($p, $xml, $vals, $index);
    xml_parser_free($p);
    $data = "";
    foreach($index as $key=>$value){
        if($key=='xml'||$key =='XML') continue;
        $tag=$vals[$value[0]]['tag'];
        $value=$vals[$value[0]]['value'];
        $data[$tag]=$value;
    }
    return $data;
}

//获取随机数
function getRandChar($length){
    $str=null;
    $strPol="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max=strlen($strPol)-1;
    for($i=0;$i<$length;$i++){
        $str.=$strPol[rand(0,$max)];
    }
    return $str;
}

//筛选/过滤条件
function checkData($data){  
    if(!isset($data['is_page'])){
        throw new TokenException([
            'msg'=>'is_page参数只能为true或者false且不能为空',
            'solelyCode'=>200003
        ]);
    }else{
        if($data['is_page']==="true"||$data['is_page']===true){
            if(!isset($data['pagesize'])||$data['pagesize']==''){
                throw new TokenException([
                    'msg'=>'每页显示数据不能为空或未设置pagesize参数',
                    'solelyCode'=>200004
                ]);
            }
            if(!isset($data['currentPage'])||$data['currentPage']==""){
                 throw new TokenException([
                    'msg'=>'当前页码不能为空或未设置currentPage参数',
                    'solelyCode'=>200005
                ]);
            }
            return [
                'code'=>2 
            ];
        }else if($data['is_page']==="false"||$data['is_page']===false){           
            if(isset($data['pagesize'])){
                throw new TokenException([
                    'msg'=>'pagesize参数无需设置',
                    'solelyCode'=>200006
                ]);
            }
            if(isset($data['currentPage'])){
                throw new TokenException([
                    'msg'=>'currentPage参数无需设置',
                    'solelyCode'=>200007
                ]);
            }
            return [
                'code'=>2 
            ];            
        }else{
            throw new TokenException([
                'msg'=>'is_page参数只能为true或者false且不能为空',
                'solelyCode'=>200003
            ]);
        }
    } 
}

//分页/筛选数据
function preAll($data){
    if(isset($data['pagesize'])){
        $size=$data['pagesize'];
    }
    if(isset($data['currentPage'])){
       $page=$data['currentPage'];
    }
    $isPage=$data['is_page']; 
    if(isset($data['searchItem'])){
        $search=$data['searchItem'];
    }else{
        $search='';
    }
    if($isPage=='true'){
        $res=[
            'pagesize'=>$size,
            'currentPage'=>$page,
            'map'=>$search,
        ];                
    }else{      
        $res=['map'=>$search];
    }
    return $res;
}

function preAll2($data){
    
    if(isset($data['searchItem'])){
        $search=$data['searchItem'];
    }else{
        $search='';
    }
        $res=[
            'map'=>$search,
        ];
    return $res;
}

function fromArrayToModel($m,$array){
    foreach($array as $key=>$value){
        $m[$key]=$value;
    }
    return $m;
}

/**
 * @param array $img 多图数组
 * @return 检查图片内容，返回字符串
 */
function initimg($img)
{
    if($img=='empty'){
        $img=json_encode([]);
    }else{
        $img=json_encode($img);
    }
    return $img;
}

/**
 * @return 短信验证码
 */

function createSMSCode($length){
    $min = pow(10,($length-1));
    $max = pow(10, $length)-1;
    return rand($min,$max);
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @return array
 */
function clist_to_tree($list, $pk='id', $pid = 'parentid', $child = 'child', $root = '')
{
    // 创建Tree
    $tree = array();
    if(is_array($list)&&$list[0]&&isset($list[0][$pid])){
        
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        };
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            
            
            $parentId =  $data[$pid];
            if(!empty($root)){
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    };
                }
            }else{

                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }else{
                    $tree[] =& $list[$key];
                };
                
            };
            
            
            
        };
        
    }else{
        $tree = $list;
    };
    
    return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree  原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array  $list  过渡用的中间数组，
 * @return array        返回排过序的列表数组
 * @author yangweijie <yangweijiester@gmail.com>
 */
function ctree_to_list($tree, $child = 'child', $order = 'id', &$list = array()){
    if(is_array($tree)) {
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if(isset($reffer[$child])){
                unset($reffer[$child]);
                ctree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby='asc');
    }
    return $list;
}

/**
 * 对查询结果集进行排序
 *
 * @access public
 * @param array $list
 *          查询结果
 * @param string $field
 *          排序的字段名
 * @param array $sortby
 *          排序类型
 *          asc正向排序 desc逆向排序 nat自然排序
 * @return array
 *
 */
function list_sort_by($list, $field, $sortby = 'asc') {
    if (is_array ( $list )) {
        $refer = $resultSet = array ();
        foreach ( $list as $i => $data )
            $refer [$i] = &$data [$field];
        switch ($sortby) {
            case 'asc' : // 正向排序
                asort ( $refer );
                break;
            case 'desc' : // 逆向排序
                arsort ( $refer );
                break;
            case 'nat' : // 自然排序
                natcasesort ( $refer );
                break;
        }
        foreach ( $refer as $key => $val )
            $resultSet [] = &$list [$key];
        return $resultSet;
    }
    return false;
}

//生成订单号
function makeOrderNo()
{
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $orderSn =
        $yCode[intval(date('Y')) - 2017] . strtoupper(dechex(date('m'))) . date(
            'd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf(
            '%02d', rand(0, 99));
    return $orderSn;
}

function makeUserNo()
{
    
    $userSn ='U'. strtoupper(dechex(date('m'))) . date(
            'd') . substr(time(), -5) . substr(microtime(), 2, 5).rand(0, 99);

    return $userSn;
}

function makeProductNo($category)
{
    
    $productNo ='P'.substr(time(), -5).substr(microtime(), 2, 5).rand(0, 99).$category;

    return $productNo;
}

function makeSkuNo($product){
        
    $no = 'S'.substr(time(), -5).rand(0, 99).$product['id']; 
    return $no;
}

function makePayNo(){      
    $no = 'M'.substr(time(), -5).rand(0, 99); 
    return $no;
}

function makeGroupNo(){      
    $no = 'G'.substr(time(), -5).rand(0, 99); 
    return $no;
}


function objectToArray ($object) {  
    if(!is_object($object) && !is_array($object)) {  
        return $object;  
    }  
  
    return array_map('objectToArray', (array) $object);  
}  












function preAdd($data){
    
    
    if(isset($data['data']['password'])){
        $data['data']['password'] = md5($data['data']['password']);
    };
    if(!isset($data['data']['thirdapp_id'])){
        $data['data']['thirdapp_id'] = Cache::get($data['token'])['thirdapp_id'];
    };
    if(!isset($data['data']['user_no'])){
        $data['data']['user_no'] = Cache::get($data['token'])['user_no'];
    };

    $data['data'] = keepNum($data['data']);
    $data['data'] = jsonDeal($data['data']);

    return $data;



}


//分页/筛选数据
function keepNum($data){

    $filterArr = ['child_array','sku_array','sku_item','spu_array','spu_item'];
    foreach ($data as $key => $value) {
        if(in_array($key,$filterArr)){
            if(is_array($value)){
                foreach ($value as $v_key => $v_value) {
                    $value[$v_key] = floatval($v_value);
                };
                $data[$key] = $value;
            }else{
                $data[$key] = floatval($value); 
            };
        };
    };

    return $data;
}



//分页/筛选数据
function jsonDeal($data){
    foreach ($data as $key => $value) {
        if(is_array($value)){
            $data[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
        };
    };
    
    return $data;
}


function resDeal($data)
{   
    $filterArr = ['bannerImg','mainImg','passage_array','express','pay','child_array','snap_product','pay','payInfo','snap_address','wx_prepay_info','sku_array','sku_item','spu_array','spu_item','custom_rule','pay_info','extra_info'];
    
    
    if(isset($data['data'])&&!empty($data['data']&&is_array($data['data']))){
        $dealData = $data['data'];
    }else if(!empty($data)&&is_array($data)){
        $dealData = $data;
    }else{
        return $data;
    };
    
    foreach ($dealData as $key => $value) {
        
        if(is_object($dealData[$key])){
            $dealData[$key] = $dealData[$key]->toArray();
            foreach ($dealData[$key] as $child_key => $child_value) {

               if(in_array($child_key,$filterArr)){
                if($child_value&&!is_array($child_value)){
                    $dealData[$key][$child_key] = json_decode($child_value,true);  
                }else if(!is_array($child_value)){
                    $dealData[$key][$child_key] = [];
                };
               };
            };
        };
        if(is_array($dealData[$key])){
            foreach ($dealData[$key] as $child_key => $child_value) {
               if(in_array($child_key,$filterArr)){
                if($child_value&&!is_array($child_value)){
                    $dealData[$key][$child_key] = json_decode($child_value,true);  
                }else if(!is_array($child_value)){
                    $dealData[$key][$child_key] = [];
                };
               };
            };
        };
        
        
    };

    if(isset($data['data'])){
        $data['data'] = $dealData;
    }else{
        $data = $dealData;
    };
    
    
    
    return $data;

}



//分页/筛选数据
function dealUpdateRes($res,$name){
    
    if($res!=0){
        throw new SuccessMessage([
            'msg'=>$name.'成功'
        ]);
    }else{
        throw new ErrorMessage([
            'msg'=>$name.'失败'
        ]);
    };
}



//分页/筛选数据
function preModelStr($data){
    
    $str = "return \$model->";
    if(isset($data['searchItem'])){
        $str = $str."where(\$data[\"searchItem\"])->";
    };
    if(isset($data['searchItemOr'])){
        $str = $str."whereOr(\$data[\"searchItemOr\"])->";
    };
    if(isset($data['order'])){
        $str = $str."order(\$data[\"order\"])->";
    }else{
        $str = $str."order('create_time', 'desc')->";
    };
    return $str;
}

//分页/筛选数据
function after($data,$arr){

    $arr = ['img','mainImg','bannerImg','headImg','child_array'];
   
    foreach ($data as $key => $value) {
       if(in_array($key,$arr)){
        $data[$key] = json_decode($value,true);
       }
    }
    
    return $data;
}


//分页/筛选数据
function chargeBlank($arr,$data){
    
    foreach ($arr as $key => $value) {
       if(!isset($data[$key])){
        $data[$key]=$value;
       };
    };

    $data = jsonDeal($data);
    return $data;

}



    /**
     * @param array 接收到的数据
     * @return 检查token参数是否设置
     */
    function checkToken ($data)
    {
        //$data=Request::instance()->param();
        if (!isset($data['token'])) {
            throw new TokenException();
        }
    }

    /**
     * @param array 接收到的数据
     * @return 检查thirdappID参数是否设置
     */
    function checkThirdID ()
    {
        $data=Request::instance()->param();
        if (!isset($data['thirdapp_id'])) {
            throw new TokenException([
                'msg'=>'缺少参数ThirdID',
                'solelyCode' => 200002
            ]);
        }
    }



    function checkValue ($data,$check)
    {

        foreach ($check as $key => $value) {
            
            if(in_array($data['serviceFuncName'],$value)){
                
                if(!array_key_exists($key,$data)){
                    if($key=='token'){
                        throw new ErrorMessage([
                            'msg'=>'缺少参数'.$key,
                            'solelyCode' => 200000
                        ]); 
                    }else{
                        throw new ErrorMessage([
                            'msg'=>'缺少参数'.$key,
                        ]);
                    }
                    
                }
            }
        }
        
    }

    function checkTokenAndScope ($data,$scope=[])
    {
        
        
        if(!Cache::get($data['token'])){

            throw new ErrorMessage([
                'msg'=>'token已失效',
                'solelyCode' => 200000
            ]);

        };
        


        if(empty($scope)){
           return $data; 
        };


        $thirdapp_id = Cache::get($data['token'])['thirdapp_id'];
       
        $thirdapp_array = Cache::get($data['token'])['thirdApp']['child_array'];
        array_push($thirdapp_array,$thirdapp_id);
        $primary_scope = Cache::get($data['token'])['primary_scope'];
        $user_no = Cache::get($data['token'])['user_no'];
        $parent_no = Cache::get($data['token'])['parent_no'];
        $user_type = Cache::get($data['token'])['user_type'];


        if(isset($data['searchItem']['thirdapp_id'])){

            if($thirdapp_id != $data['searchItem']['thirdapp_id']&&!in_array($data['searchItem']['thirdapp_id'],$thirdapp_array)){
                if($primary_scope<60){
                   throw new ErrorMessage([
                        'msg'=>'项目权限不符',
                    ]); 
                };
            };
        }else{
            $data['searchItem']['thirdapp_id'] = ['in',$thirdapp_array];
        };


        if(isset($data['data']['thirdapp_id'])){
            
            if($thirdapp_id != $data['data']['thirdapp_id']&&!in_array($data['data']['thirdapp_id'],$thirdapp_array)){
                if($primary_scope<60){
                   throw new ErrorMessage([
                        'msg'=>'项目权限不符',
                    ]); 
                }
            }
            
        }else{
            $data['data']['thirdapp_id'] = $thirdapp_id;
        };
        

        $pass = false;
        foreach ($scope as $key => $value) {
            if($user_type==$key){

                foreach ($value as $c_key => $c_value) {

                    if($primary_scope<=$c_key){

                        if(empty($c_value)){
                            throw new ErrorMessage([
                                'msg'=>'权限不足',
                            ]);
                        };
                        $pass = true;
                        $check_no = '';
                        $check_type = $user_type;
                        
                        if(isset($data['data']['user_no'])&&isset($data['searchItem']['user_no'])){
                            throw new ErrorMessage([
                                'msg'=>'不允许更新user_no',
                            ]);
                        };

                        if(isset($data['data']['user_no'])){
                            $check_no = $data['data']['user_no'];
                        };

                        if(isset($data['searchItem']['user_no'])){
                            $check_no = $data['searchItem']['user_no'];
                        };

                        if($check_no){
                            $model = new User();
                            $map['user_no'] = $check_no;
                            $checkUserInfo = $model->where($map)->find(); 
                            if($checkUserInfo){
                                $check_type = $checkUserInfo['user_type'];
                            }else{
                                throw new ErrorMessage([
                                    'msg'=>'传递的user_no有误',
                                ]);
                            };
                        };

                        if(isset($data['searchItem']['user_type'])){
                            $check_type = $data['searchItem']['user_type'];
                        };


                        
                        foreach ($c_value as $cc_key => $cc_value) {

                            if($check_type==$cc_key){
                                

                                if($cc_value=='isMe'){
                                    $data['searchItem']['user_no'] = $user_no;
                                    if(isset($data['data'])){
                                        $data['data']['user_no'] = $user_no;
                                    };
                                };

                                if($cc_value=='canChild'){

                                    if(isset($checkUserInfo)){
                                        if($checkUserInfo['user_no']!=$user_no&&$checkUserInfo['parent_no']!=$user_no){
                                            throw new ErrorMessage([
                                                'msg'=>'权限不符',
                                            ]);
                                        };
                                    }else{

                                        $res = (new Distribution())->where('parent_no','=',$user_no)->select();
                                        
                                        if($res){
                                            $user_array = [];
                                            foreach ($res as $ccc_key => $ccc_value) {
                                                array_push($user_array,$ccc_value['child_no']);
                                            };
                                            array_push($user_array,$user_no);
                                            $data['searchItem']['user_no'] = ['in',$user_array];
                                        }else{
                                            $data['searchItem']['user_no'] = $user_no;
                                        };
                                        
                                        if(isset($data['data'])){
                                            $data['data']['user_no'] = $user_no;
                                        };
                                    };
                                    
                                };
                                break;
                            };
                            
                        };
                        
                        break;    
                    };

                };

                break;
                
            };

        };
        
        if(!$pass){
            throw new ErrorMessage([
                'msg'=>'权限不足',
            ]);
        }else{
            return $data;
        };
    

        
        
    }


    function generateToken(){
        $randChar = getRandChar(32);
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        $tokenSalt = config('secure.token_salt');
        return md5($randChar . $timestamp . $tokenSalt);
    }






    function preSearch($data){
    

        
        

        if(isset($data['searchItem'])){
            $data['map'] = [];
            $data['map'] = array_merge($data['map'],objectToArray($data['searchItem']));
            unset($data['searchItem']);
        }

        if(isset($data['searchItemOr'])){
            $data['mapOr'] = [];
            $data['mapOr'] = array_merge($data['mapOr'],objectToArray($data['searchItemOr']));
            unset($data['searchItemOr']);
        }

        

        return $data;

    }


    function changeIndexArray($indexName,$data){
        $newArray = array();
        foreach ($data as $key => $value) {
            if(isset($value[$indexName])){
                $newArray[$value[$indexName]] = $value;
            };
        };
        return $newArray;
    }


    function changeIndexArrayUnion($indexName,$data){
        $newArray = array();
        foreach ($data as $key => $value) {
            if(isset($value[$indexName])&&isset($newArray[$value[$indexName]])){
                $newArray[$value[$indexName]][$value['id']] = $value;
            }else if(isset($value[$indexName])&&!isset($newArray[$value[$indexName]])){
                $newArray[$value[$indexName]] = [];
                $newArray[$value[$indexName]][$value['id']] = $value;
            };
        };
        return $newArray;
    }

    function exportExcel($expTitle,$expCellName,$expTableData,$fileName){ 
        import('phpexcel.PHPExcel', EXTEND_PATH);
        import('phpexcel.PHPExcel.IOFactory', EXTEND_PATH);
        //文件名称 
        $xlsTitle = iconv('utf-8','gb2312',$expTitle);
        $cellNum = count($expCellName);  
        $dataNum = count($expTableData);
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');


        //合并单元格     
        //$objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');    
        for($i=0;$i<$cellNum;$i++){  
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]);   
        }     
        for($i=0;$i<$dataNum;$i++){  
          for($j=0;$j<$cellNum;$j++){  
             $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2), $expTableData[$i][$expCellName[$j][0]]);  
          }               
        };

        ob_end_clean();
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');  
        header("Content-Disposition:attachment;filename=$fileName.xls");
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        //Loader::import('phpexcel.PHPExcel.IOFactory'); 
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007'); 
        
        ob_start();
        $objWriter->save('php://output');
        $xlsData = ob_get_contents();
        ob_end_clean();
        
        return 'data:application/vnd.ms-excel;base64,'.base64_encode($xlsData);
        
           
    }


    function checkSmsAuth($data){
        if(isset($data['smsAuth'])){
            if(Cache::get('smsCode'.$data['smsAuth']['phone'])){
               if($data['smsAuth']['code']!=Cache::get('smsCode'.$data['smsAuth']['phone'])['code']){
                    throw new ErrorMessage([
                        'msg'=>'验证码错误',
                    ]);
                }else{
                    $data['smsAuth'] = 'solelyPass';
                }; 
            }else{
                throw new ErrorMessage([
                    'msg'=>'验证码失效',
                ]);
            };
        };
        return $data;
    }


    function transformExcel($data){
        import('phpexcel.PHPExcel', EXTEND_PATH);
        import('phpexcel.PHPExcel.IOFactory', EXTEND_PATH);
        $file = request()->file();

        if($file&&isset($file['excel'])){

            $file = $file['excel'];
            if($file->getInfo()['tmp_name']){
                $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
                $obj_PHPExcel =$objReader->load($file->getInfo()['tmp_name'], $encode = 'utf-8');  
                //加载文件内容,编码utf-8
                echo "<pre>";
                $excel_array=$obj_PHPExcel->getsheet(0)->toArray();
                $tableName = $excel_array[0];
                array_shift($excel_array); 
            };
            $modelData = [];
            foreach ($excel_array as $key => $value) {
                foreach ($value as $c_key => $_value) {
                    $modelData[$key][$tableName[$c_key]] =  $_value;
                };
            };
            
            $data['dataArray'] = $modelData;

        };
        return $data;
    }


    