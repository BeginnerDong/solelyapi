<?php

namespace app\api\service\base;

use think\Exception;

use think\Model;

use think\Cache;

use think\Request as Request; 

use app\api\service\beforeModel\Common as BeforeModel;

use app\api\validate\CommonValidate;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;


class FtpFile{

    function __construct(){

    }
	
    
    public static function upload($data,$inner=false)
    {       
		(new CommonValidate())->goCheck('one',$data);
        $userinfo = Cache::get($data['token']);
		
		if(isset($data['stream'])||isset($data['url'])){
			if(isset($data['url'])){
				$data['stream'] = file_get_contents($data['url'], 'r');
			};
			$filePath = ROOT_PATH . 'public' . DS . 'uploads/'.$userinfo['thirdapp_id'].'/'.date('Ymd') .'/';
			$file_name = get_rand_str(8).time().'.'.$data['ext'];
			is_dir($filePath) OR mkdir($filePath, 0777, true);
		    $res = file_put_contents($filePath.$file_name,$data['stream']);
			if($res){
				return self::addFileInfo($file_name,$userinfo,$data,$inner);
			};
		};
		
		
        $chunkSize = $data['chunkSize'];
		$md5 = $data['md5'];
		$totalSize = request()->param()['totalSize'];
		$start = request()->param()['start'];
		$chunkNum = ceil($totalSize/$chunkSize);
		$chunk_index = $start/$chunkSize;
		$tempPath = ROOT_PATH . 'public' . DS . 'uploads/temp/'.$md5.'/';
		$unFinishIndex = [];
		$chunk_start = 0;
		
		if($chunkNum==1){
			$file = request()->file();
			$file = $file['file'];
			if($file){
				$filePath = ROOT_PATH . 'public' . DS . 'uploads/'.$userinfo['thirdapp_id'].'/'.date('Ymd') .'/';
				$file_name = get_rand_str(8).time().'.'.$data['ext'];
				$info = $file->rule('uniqid')->move($filePath,$file_name);
				return self::addFileInfo($info->getSaveName(),$userinfo,$data,$inner);
			};
		};
		
		
		
		for ( $i =0; $i < $chunkNum; $i ++ ) {
			if(!file_exists($tempPath . $i . '.'.$data['ext'])){
				$unFinishIndex[] = $i;
			}
		};
		
		if(count($unFinishIndex)>0){
			$key = array_search($chunk_index,$unFinishIndex);
			if($key !== false){
				array_splice($unFinishIndex,$key,1);
				if(count($unFinishIndex)>0){
					$chunk_start = $unFinishIndex[0]*$chunkSize;
				};
			}else{
				return ['chunk_start'=>$unFinishIndex[0]*$chunkSize,'finishCount'=>($chunkNum-count($unFinishIndex))];
			}
		}else{
			$res = self::merge($md5,$chunkNum,$userinfo['thirdapp_id'],$data['ext']);
			return self::addFileInfo($res['file_name'],$userinfo,$data,$inner);
		};
		
		$object_info = request()->file('file');
		$object = $object_info->rule('uniqid')->move($tempPath,$chunk_index.'.'.$data['ext']);
		$originName = $object->getInfo();
		$saveName = $object->getSaveName();
		if($object){
			if(count($unFinishIndex)==0){
				$res = self::merge($md5,$chunkNum,$userinfo['thirdapp_id'],$data['ext']);
				return self::addFileInfo($res['file_name'],$userinfo,$data,$inner);
			}else{
				return [
					'chunk_start'=>$unFinishIndex[0]*$chunkSize,
					'finishCount'=>($chunkNum-count($unFinishIndex))
				];
			};
		}else{
		    throw new ErrorMessage([
		        'msg' => 'chunk上传失败',
		    ]);
		};

    }
	
	
	//最终合并文件
	public static function merge($md5,$chunkNum,$thirdapp_id,$ext)
	{
	    $md5 = request()->param()['md5'];
		$tempPath = ROOT_PATH . 'public' . DS . 'uploads/temp/'.$md5.'/';
		$filePath = ROOT_PATH . 'public' . DS . 'uploads/'.$thirdapp_id.'/'.date('Ymd') .'/';
		$file_name = get_rand_str(8).time().'.'.$ext;
	    if(!is_dir($filePath)){
	        mkdir($filePath);
	    };
		if(is_dir($tempPath)) {
			for ( $i =0; $i < $chunkNum; $i ++ ) {
				$_file = file_get_contents($tempPath. $i .'.'.$ext);
				$_res = file_put_contents($filePath .$file_name,$_file,FILE_APPEND);
				if($_res){
				    unlink($tempPath. $i .'.'.$ext);
				}else{
					throw new ErrorMessage([
					    'msg' => '合并失败',
					]);
				};
			};
		};
		rmdir($tempPath);
		$_hash = hash_file('sha1', $filePath .$file_name);
		if($_hash){
			return [
				'filePath'=>$filePath,
				'file_name'=>$file_name,
			];
		};
		
		
	
	}
	
	//图片存入数据库
	public static function addFileInfo($file_name,$userinfo,$data,$inner=false)
	{
		

		
	    $url = config('secure.base_url').'/public/uploads/'.$userinfo['thirdapp_id'].'/'.date('Ymd').'/'.$file_name;
	    $modelData = [];
	    $modelData['data'] = array(
	    	'origin_name'=>$data['originName'],
	        "title" => $file_name,
	        "thirdapp_id" => $userinfo['thirdapp_id'],
	        "user_no" => $userinfo['user_no'],
	        "path" => $url,
	        "prefix"  => 'uploads/'.$userinfo['thirdapp_id'],
	        "size" => $data['totalSize'],
	        "type" => $data['ext'],
	        "origin" => 2,
	        "behavior" => isset($data['behavior'])?$data['behavior']:1,
	        "param" => isset($data['param'])?$data['param']:1,
	        "create_time" => time(),
	    
	    );
	    $modelData['FuncName'] = 'add';
		
	    $res = BeforeModel::CommonSave('File',$modelData);
		if ($res>0) {
		    if(!$inner){
				
		        throw new SuccessMessage([
		            'msg'=>'图片上传成功',
		            'info'=>['url'=>$url],
					'finishCount'=>(ceil($data['totalSize']/$data['chunkSize'])-1)
		        ]);
		
		    }else{
		        return $url;
		    };
		
		}else{
		    throw new ErrorMessage([
		        'msg' => '图片信息写入数据库失败',
		    ]);
		};
		
	}
	
	
	
	
	
	
	
	
}