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


	function curl_post_string($url,$string)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $string);
		curl_setopt(
			$ch, CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/x-www-form-urlencoded'
			)
		);
		$data = curl_exec($ch);
		curl_close($ch);
		return ($data);
	}


	/**
	 * post发送数组
	 * form-data
	 */
	function curl_post_data($url, array $params = array())
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
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
	function xml2array($xml)
	{
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
	function getRandChar($length)
	{
		$str=null;
		$strPol="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
		$max=strlen($strPol)-1;
		for($i=0;$i<$length;$i++){
			$str.=$strPol[rand(0,$max)];
		}
		return $str;
	}


	//筛选/过滤条件
	function checkData($data)
	{
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
	function preAll($data)
	{
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


	function fromArrayToModel($m,$array)
	{
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
	function createSMSCode($length)
	{
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
	function clist_to_tree($list, $pk='id', $pid = 'parentid', $child = 'children', $root = '')
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
	function ctree_to_list($tree, $child = 'child', $order = 'id', &$list = array())
	{
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
	function list_sort_by($list, $field, $sortby = 'asc')
	{
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


	//生成用户NO
	function makeUserNo()
	{
		
		$userSn ='U'. strtoupper(dechex(date('m'))) . date(
				'd') . substr(time(), -5) . substr(microtime(), 2, 5).rand(0, 99);

		return $userSn;
	}


	//生产产品NO
	function makeProductNo($category)
	{
		$productNo ='P'.substr(time(), -5).substr(microtime(), 2, 5).rand(0, 99).$category;

		return $productNo;
	}


	//生成SKU NO
	function makeSkuNo($product)
	{
		$no = 'S'.substr(time(), -5).rand(0, 99).$product['id']; 
		return $no;
	}


	//生成支付NO
	function makePayNo()
	{
		$no = 'M'.substr(time(), -5).rand(0, 99); 
		return $no;
	}


	//生成团购NO
	function makeGroupNo()
	{
		$no = 'G'.substr(time(), -5).rand(0, 99); 
		return $no;
	}


	//对象转数组
	function objectToArray ($object)
	{
		if(!is_object($object) && !is_array($object)) {  
			return $object;  
		}  

		return array_map('objectToArray', (array) $object);  
	}


	//数据整理
	//字符转数字
	function keepNum($data)
	{

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


	//写入数据库
	//数组转json字符串
	function jsonDeal($data)
	{
		foreach ($data as $key => $value) {
			if(is_array($value)){
				$data[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
			};
		};
		
		return $data;
	}


	//数据库读取
	//json字符串转数组
	function resDeal($data)
	{
		$filterArr = ['bannerImg','mainImg','passage_array','express','pay','child_array','snap_product','snap_coupon','pay','payInfo','snap_address','wx_prepay_info','sku_array','sku_item','spu_array','spu_item','custom_rule','pay_info','extra_info','img_array','file','payAfter'];

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


	//返回前端信息模板
	function dealUpdateRes($res,$name)
	{
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


	//整理搜索与排序参数
	function preModelStr($data)
	{
		
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


	function after($data,$arr)
	{

		$arr = ['img','mainImg','bannerImg','headImg','child_array'];
	   
		foreach ($data as $key => $value) {
		   if(in_array($key,$arr)){
			$data[$key] = json_decode($value,true);
		   }
		}
		
		return $data;
	}


	//数据库写入
	//检查空字段
	function chargeBlank($arr,$data)
	{

		foreach ($arr as $key => $value) {
		   if(!isset($data[$key])){
			$data[$key] = $value;
		   };
		};

		$data = jsonDeal($data);
		return $data;

	}


	/**
	 * @param array 接收到的数据
	 * @return 检查token参数是否设置
	 */
	function checkToken($data)
	{
		//$data=Request::instance()->param();
		if (!isset($data['token'])) {
			throw new TokenException();
		}
	}


	function checkValue($data,$check)
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


	function checkTokenAndScope($data,$scope=[])
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
		$check_no = '';
		$check_type = -1;

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

		if ($check_type<0) {
			if(isset($data['searchItem']['user_type'])){
				$check_type = $data['searchItem']['user_type'];
			}elseif (isset($data['data']['user_type'])) {
				$check_type = $data['data']['user_type'];
			}else{
				$check_type = $user_type;
			};
		}

		if (is_array($check_type)) {
			
			foreach ($check_type[1] as $k => $v) {
				
				$check_array = $user_type.'-'.$primary_scope.'-'.$v;

				foreach ($scope as $key => $value){

					if($check_array==$key){

						if(empty($value)){

							throw new ErrorMessage([
								'msg'=>'权限不足',
							]);
						};

						$pass = true;

						if (!empty($value['auth'])) {
							
							if($value['auth']=='isMe'){

								$data['searchItem']['user_no'] = $user_no;
								if(isset($data['data'])){
									$data['data']['user_no'] = $user_no;
								};
							};

							if($value['auth']=='canChild'){

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
										foreach ($res as $c_key => $c_value) {
											array_push($user_array,$c_value['child_no']);
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

							if($value['auth']=='All'){

								if(isset($data['data'])&&!isset($data['data']['user_no'])&&!isset($data['searchItem']['user_no'])){
									$data['data']['user_no'] = $user_no;
								};

								if (isset($data['searchItem']['user_no'])) {
									$data['data']['user_no'] = $data['searchItem']['user_no'];
								}

							};

						}else{

							$data['searchItem']['user_no'] = $user_no;
							if(isset($data['data'])){
								$data['data']['user_no'] = $user_no;
							};

						};

						if (!empty($value['addLimit'])&&isset($data['FuncName'])&&$data['FuncName']=='add') {
							
							foreach ($value['addLimit'] as $key => $value) {
								
								if (isset($data['data'][$value])) {
									
									unset($data['data'][$value]);

								}

							}

						};

						if (!empty($value['updateLimit'])&&isset($data['FuncName'])&&$data['FuncName']=='update') {
							
							foreach ($value['addLimit'] as $key => $value) {
								
								if (isset($data['data'][$value])) {
									
									unset($data['data'][$value]);

								}

							}

						}

						if (!empty($value['getLimit'])) {
							
							$data['getLimit'] = $value['getLimit'];

						}

						if (isset($data['FuncName'])&&$data['FuncName']=='add') {
							
							$data['data']['user_type'] = $v;
						}
						
					};

				};

			}

		}else{

			$check_array = $user_type.'-'.$primary_scope.'-'.$check_type;

			foreach ($scope as $key => $value){

				if($check_array==$key){

					if(empty($value)){
						throw new ErrorMessage([
							'msg'=>'权限不足',
						]);
					};

					$pass = true;

					if (!empty($value['auth'])) {
						
						if($value['auth']=='isMe'){

							$data['searchItem']['user_no'] = $user_no;
							if(isset($data['data'])){
								$data['data']['user_no'] = $user_no;
							};
						};

						if($value['auth']=='canChild'){

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
									foreach ($res as $c_key => $c_value) {
										array_push($user_array,$c_value['child_no']);
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

						if($value['auth']=='All'){

							if(isset($data['data'])&&!isset($data['data']['user_no'])&&!isset($data['searchItem']['user_no'])){
								$data['data']['user_no'] = $user_no;
							};

							if (isset($data['searchItem']['user_no'])) {
								$data['data']['user_no'] = $data['searchItem']['user_no'];
							}

						}

					}else{

						$data['searchItem']['user_no'] = $user_no;
						if(isset($data['data'])){
							$data['data']['user_no'] = $user_no;
						};

					};

					if (!empty($value['addLimit'])&&isset($data['FuncName'])&&$data['FuncName']=='add') {
						
						foreach ($value['addLimit'] as $key => $value) {
							
							if (isset($data['data'][$value])) {
								
								unset($data['data'][$value]);

							}

						}

					};

					if (!empty($value['updateLimit'])&&isset($data['FuncName'])&&$data['FuncName']=='update') {
						
						foreach ($value['addLimit'] as $key => $value) {
							
							if (isset($data['data'][$value])) {
								
								unset($data['data'][$value]);

							}

						}

					}

					if (!empty($value['getLimit'])) {
						
						$data['getLimit'] = $value['getLimit'];

					}

					if (isset($data['FuncName'])&&$data['FuncName']=='add') {
						
						$data['data']['user_type'] = $check_type;
					}
					
				};

			};

		}

		if(!$pass){
			throw new ErrorMessage([
				'msg'=>'权限不足',
			]);
		}else{
			return $data;
		};
		
	}


	//生成token
	function generateToken()
	{
		$randChar = getRandChar(32);
		$timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
		$tokenSalt = config('secure.token_salt');
		return md5($randChar . $timestamp . $tokenSalt);
	}


	function preSearch($data)
	{

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


	function changeIndexArray($indexName,$data)
	{
		$newArray = array();
		foreach ($data as $key => $value) {
			if(isset($value[$indexName])){
				$newArray[$value[$indexName]] = $value;
			};
		};
		return $newArray;
	}


	function changeIndexArrayUnion($indexName,$data)
	{
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


	//导出EXCEL
	function exportExcel($excelOutput,$expTableData)
	{
		
		import('phpexcel.PHPExcel', EXTEND_PATH);
		import('phpexcel.PHPExcel.IOFactory', EXTEND_PATH);

		$expTitle = $excelOutput['expTitle'];
		$expCellName = $excelOutput['expCellName'];
		$fileName = $excelOutput['fileName'];
		$height = isset($excelOutput['height'])?$excelOutput['height']:'';

		//文件名称 
		$xlsTitle = iconv('utf-8','gb2312',$expTitle);
		$cellNum = count($expCellName);  
		$dataNum = count($expTableData);
		$objPHPExcel = new \PHPExcel();
		$cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

		for($i=0;$i<$cellNum;$i++){  
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i]['title']); 
			if(isset($expCellName[$i]['width'])){
				$objPHPExcel->getColumnDimension($cellName[$i])->setWidth($expCellName[$i]['width']);
			};
		};
		
		$objPHPExcel -> getActiveSheet() -> getColumnDimension(PHPExcel_Cell::stringFromColumnIndex(0)) -> setAutoSize(true);  
		for($i=0;$i<$dataNum;$i++){  
			
			if($height){
				$objPHPExcel->getActiveSheet()->getRowDimension($i)->setRowHeight($height);
			}; 
		  for($j=0;$j<$cellNum;$j++){ 

			$finalValue = '';
			for ($c_i=0; $c_i < count($expCellName[$j]['key']); $c_i++) {
				if($c_i==0){
					if(isset($expTableData[$i][$expCellName[$j]['key'][$c_i]])){
						$finalValue = $expTableData[$i][$expCellName[$j]['key'][$c_i]];
					};
				}else{
					if(isset($finalValue[$expCellName[$j]['key'][$c_i]])){
						$finalValue = $finalValue[$expCellName[$j]['key'][$c_i]];
					}else{
						$finalValue = '';
					};
				};
			};

			if($expCellName[$j]['type']=='string'){
				$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2),$finalValue);
			};
			if($expCellName[$j]['type']=='image'&&$finalValue){
				$objDrawing[$i+2] = new \PHPExcel_Worksheet_Drawing();
				if(strlen($finalValue)>$expCellName[$j]['url_intercepte_start']){
					
					$path = substr($finalValue,$expCellName[$j]['url_intercepte_start']);
					if(file_exists(ROOT_PATH.$path)){
						
						$objDrawing[$i+2]->setPath(ROOT_PATH.$path);
						// 设置宽度高度
						$objDrawing[$i+2]->setHeight($expCellName[$j]['image_height']);//照片高度
						$objDrawing[$i+2]->setWidth($expCellName[$j]['image_width']); //照片宽度
						/*设置图片要插入的单元格*/
						$objDrawing[$i+2]->setCoordinates($cellName[$j].($i+2));
						// 图片偏移距离
						$objDrawing[$i+2]->setOffsetX(12);
						$objDrawing[$i+2]->setOffsetY(12);
						$objDrawing[$i+2]->setWorksheet($objPHPExcel->getActiveSheet());
					};  
				};
			};
		  };               
		};

		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$xlsTitle.'.xls"');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		$objPHPExcel->disconnectWorksheets();

	}


	//检查手机验证码
	function checkSmsAuth($data)
	{
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


	//导入EXCEL
	function transformExcel($data)
	{
		import('phpexcel.PHPExcel', EXTEND_PATH);
		import('phpexcel.PHPExcel.IOFactory', EXTEND_PATH);
		$file = request()->file();

		if($file&&isset($file['excel'])){

			$file = $file['excel'];
			if($file->getInfo()['tmp_name']){
				$objReader =\PHPExcel_IOFactory::createReader('Excel2007');
				$obj_PHPExcel =$objReader->load($file->getInfo()['tmp_name'], $encode = 'utf-8');  
				//加载文件内容,编码utf-8
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


	/**
	 * 字符串转码GBK
	 * 传入string,转码后返回
	 */
	function strToGBK($strText)
	{
		$encode = mb_detect_encoding($strText, array('UTF-8','GB2312','GBK'));
		if($encode == "UTF-8")
		{
			return @iconv('UTF-8','GB18030',$strText);
		}
		else
		{
			return $strText;
		}
	}


	function checkSearchAuth($data,$scope=[])
	{

		if(empty($scope)){
		   return $data; 
		};
		if(!Cache::get($data['token'])){
			throw new ErrorMessage([
				'msg'=>'token已失效',
				'solelyCode' => 200000
			]);
		};
		$primary_scope = Cache::get($data['token'])['primary_scope'];
		$user_no = Cache::get($data['token'])['user_no'];
		$parent_no = Cache::get($data['token'])['parent_no'];
		$user_type = Cache::get($data['token'])['user_type'];
		$thirdapp_id = Cache::get($data['token'])['thirdapp_id'];
		$thirdapp_array = Cache::get($data['token'])['thirdApp']['child_array'];
		array_push($thirdapp_array,$thirdapp_id);
		if(isset($data['searchItem']['thirdapp_id'])){
			if((is_array($data['searchItem']['thirdapp_id'])&&!empty(array_diff($data['searchItem']['thirdapp_id'][1], $thirdapp_array)))
			||!in_array($data['searchItem']['thirdapp_id'],$thirdapp_array)){
				if($primary_scope<60){
				   throw new ErrorMessage([
						'msg'=>'项目权限不符',
					]); 
				};
			};
		}else{
			$data['searchItem']['thirdapp_id'] = ['in',$thirdapp_array];
		};
		if(!isset($data['searchItem']['user_type'])&&isset($data['searchItem']['user_no'])){
			$model = new User();
			$map['user_no'] = $data['searchItem']['user_no'];
			$checkUserInfo = $model->where($map)->find(); 
			if($checkUserInfo){
				$check_type = $checkUserInfo['user_type'];
			}else{
				throw new ErrorMessage([
					'msg'=>'传递的user_no有误',
				]);
			};
		}else if(isset($data['searchItem']['user_type'])){
			$check_type = $data['searchItem']['user_type'];
		}else{
			$data['searchItem']['user_no'] = $user_no;
			return $data;
		};

		$passNo = [];
		$pass = true;
		$scope_item = $scope[$user_type.'-'.$primary_scope.'-'.$check_type];
		
		if(!isset($scope_item)){
			throw new ErrorMessage([
				'msg'=>'权限不足',
			]);
		};
		if($scope_item['auth']=='isMe'){
			$passNo = [$user_no];
		};
		
		if($scope_item['auth']=='canChild'){
			if(isset($checkUserInfo)&&$checkUserInfo['parent_no']==$user_no){
				$passNo = [$user_no];
			}else if(!isset($checkUserInfo)){
				$res = (new Distribution())->where('parent_no','=',$user_no)->select();
				if($res){
					$user_array = [];
					foreach ($res as $c_key => $c_value) {
						array_push($user_array,$c_value['child_no']);
					};
					array_push($user_array,$user_no);
					$passNo = ['in',$user_array];
				}else{
					$passNo = [$user_no];
				};
			}
		};
		
		if($scope_item['auth']!='All'){
			if(isset($data['searchItem']['user_no'])){
				if(is_array($data['searchItem']['user_no'])&&!empty(array_diff($data['searchItem']['user_no'][1], $passNo))){
					$pass = false;
				}else{
					$pass = in_array($data['searchItem']['user_no'],$passNo);
				};
			}else{
				$num = count($passNo);
				if($num==0){
					$pass = false;
				}else if($num==1){
					$data['searchItem']['user_no'] = $passNo[0];
				}else{
					$data['searchItem']['user_no'] = ['in',$passNo];
				};
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


	function checkDataAuth($data,$scope=[])
	{
		
		if(empty($scope)){
		   return $data; 
		};
		if(!Cache::get($data['token'])){
			throw new ErrorMessage([
				'msg'=>'token已失效',
				'solelyCode' => 200000
			]);
		};
		$primary_scope = Cache::get($data['token'])['primary_scope'];
		$user_no = Cache::get($data['token'])['user_no'];
		$parent_no = Cache::get($data['token'])['parent_no'];
		$user_type = Cache::get($data['token'])['user_type'];
		$thirdapp_id = Cache::get($data['token'])['thirdapp_id'];
		$thirdapp_array = Cache::get($data['token'])['thirdApp']['child_array'];
		array_push($thirdapp_array,$thirdapp_id);
		if(isset($data['data']['thirdapp_id'])){
			
			if(!in_array($data['data']['thirdapp_id'],$thirdapp_array)){
				if($primary_scope<60){
				   throw new ErrorMessage([
						'msg'=>'项目权限不符',
					]); 
				};
			};
			
		}else if($data['FuncName']=='add'){
			$data['data']['thirdapp_id'] = $thirdapp_id;
		};
		
		if(!isset($data['data']['user_no'])&&$data['FuncName']=='add'){
			$data['data']['user_no'] = $user_no;
			$data['data']['user_type'] = $user_type;
			return $data;
		}else if(isset($data['data']['user_no'])&&$data['FuncName']=='update'){
			
			
			$model = new User();
			$map['user_no'] = $data['data']['user_no'];
			$checkUserInfo = $model->where($map)->find(); 
			$data['data']['user_type'] = $checkUserInfo['user_type'];
			if($checkUserInfo){
				$check_type = $checkUserInfo['user_type'];
			}else{
				throw new ErrorMessage([
					'msg'=>'传递的user_no有误',
				]);
				return;
			};
		}else{
			return $data;
		};

		$passNo = [];
		$pass = true;
		$scope_item = $scope[$user_type.'-'.$primary_scope.'-'.$check_type];
		
		if($scope_item['auth']=='isMe'){
			$passNo = [$user_no];
		};
		
		if($scope_item['auth']=='canChild'&&$checkUserInfo['parent_no']==$user_no){
			$passNo = [$user_no];	
		};
		
		if($scope_item['auth']!='All'){			
			$pass = in_array($data['data']['user_no'],$passNo);
		};

		if(!$pass){
			throw new ErrorMessage([
				'msg'=>'权限不足',
			]);
		}else{
			return $data;
		};
		
	}


	//随机字符串
	function get_rand_str($len)
	{
		$str = "1234567890asdfghjklqwertyuiopzxcvbnmASDFGHJKLZXCVBNMPOIUYTREWQ";
		return substr(str_shuffle($str),0,$len);
	}


	function GetBrowser()
	{
		if(!empty($_SERVER['HTTP_USER_AGENT'])){
			$br = $_SERVER['HTTP_USER_AGENT'];
			if (preg_match('/MSIE/i',$br)){
			 $br = 'MSIE';
			}elseif (preg_match('/Firefox/i',$br)){
			 $br = 'Firefox';
			}elseif (preg_match('/Chrome/i',$br)){
			 $br = 'Chrome';
			}elseif (preg_match('/Safari/i',$br)){
			 $br = 'Safari';
			}elseif (preg_match('/Opera/i',$br)){
			 $br = 'Opera';
			}else {
			 $br = 'Other';
			};
			return json_encode("浏览器为".$br);
		}else{
			return "获取浏览器信息失败！";
		};
		var_dump(999999);   
	}


	function Getip()
	{
		if (! empty($_SERVER["HTTP_CLIENT_IP"])) {
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		}
		if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { // 获取代理ip
			$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		}
		if (isset($ip)) {
			$ips = array_unshift($ips, $ip);
		}
		
		$count = count($ips);
		for ($i = 0; $i < $count; $i ++) {
			if (! preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) { // 排除局域网ip
				$ip = $ips[$i];
				break;
			}
		}
		$tip = empty($_SERVER['REMOTE_ADDR']) ? $ip : $_SERVER['REMOTE_ADDR'];
		if ($tip == "127.0.0.1") { // 获得本地真实IP
			return $this->get_onlineip();
		} else {
			return $tip;
		}
	}


	function Getaddress($ip = '')
	{
		if (empty($ip)) {
			$ip = Getip();
		}
		
		$ipadd = file_get_contents("http://ip.360.cn/IPQuery/ipquery?ip=" . $ip); // 根据新浪api接口获取
		var_dump($ipadd);
		if ($ipadd) {
			$charset = iconv("gbk", "utf-8", $ipadd);
			preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $charset, $ipadds);
			var_dump($charset);
			return $ipadds; // 返回一个二维数组
		} else {
			return "addree is none";
		}
	}


	function clientOS()
	{

		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);

		if(strpos($agent, 'windows nt')) {
			$platform = 'windows';
		} elseif(strpos($agent, 'macintosh')) {
			$platform = 'mac';
		} elseif(strpos($agent, 'ipod')) {
			$platform = 'ipod';
		} elseif(strpos($agent, 'ipad')) {
			$platform = 'ipad';
		} elseif(strpos($agent, 'iphone')) {
			$platform = 'iphone';
		} elseif (strpos($agent, 'android')) {
			$platform = 'android';
		} elseif(strpos($agent, 'unix')) {
			$platform = 'unix';
		} elseif(strpos($agent, 'linux')) {
			$platform = 'linux';
		} else {
			$platform = 'other';
		}

		return $platform;
	}


	function takeImgList($content)
	{
		$preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';//匹配img标签的正则表达式
		preg_match_all($preg, $content, $imgArray);//这里匹配所有的img
		return $imgArray;
	}
	
	
	//删除指定文件夹以及文件夹下的所有文件
	function deldir($dir) {
		//先删除目录下的文件：
		$dh=opendir($dir);
		while ($file=readdir($dh)) {
			if($file!="." && $file!="..") {
				$fullpath = $dir."/".$file;
				if(!is_dir($fullpath)) {
					unlink($fullpath);
				}else{
				deldir($fullpath);
				}
			}
		}

		closedir($dh);
		//删除当前文件夹：
		if(rmdir($dir)) {
			return true;
		}else{
			return false;
		}
	}
