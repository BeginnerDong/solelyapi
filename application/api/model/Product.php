<?php



namespace app\api\model;





use think\Model;

use app\api\model\Label;

use app\api\model\Sku;

use app\api\model\Relation;

use app\lib\exception\SuccessMessage;

use app\lib\exception\ErrorMessage;



class Product extends BaseModel

{





    public static function dealAdd($data)

    {   



        $standard = ['title'=>'','description'=>'','content'=>'','mainImg'=>[],'bannerImg'=>[],'price'=>0,'stock'=>'','discount'=>'','type'=>1,'category_id'=>'','sku_array'=>[],'sku_item'=>[],'spu_array'=>[],'spu_item'=>[],'passage1'=>'','passage2'=>'','passage_array'=>[],'view_count'=>'','listorder'=>'','create_time'=>time(),'update_time'=>'','delete_time'=>'','deadline'=>'','thirdapp_id'=>'','user_no'=>'','standard'=>0,'onShelf'=>1,'status'=>1,'product_no'=>makeProductNo($data['data']['category_id'])];

        $data['data'] = chargeBlank($standard,$data['data']);



        if($data['data']['category_id']!=0){

            $res = Label::get(['id' => $data['data']['category_id']]);

            if(!$res){

                throw new ErrorMessage([

                    'msg' => '关联信息有误',

                ]);

            };

        };

        

        $label = [];

        if(isset($data['data']['spu_array'])){
            foreach ($data['data']['spu_array'] as $key => $value) {
                $data['data']['spu_array'][$key] = (int)$value;
            };
            $label = array_keys(array_flip($label) + array_flip(json_decode($data['data']['spu_array'])));

        };

        if(isset($data['data']['spu_item'])){
            foreach ($data['data']['spu_item'] as $key => $value) {
                $data['data']['spu_item'][$key] = (int)$value;
            };
            $label = array_keys(array_flip($label) + array_flip(json_decode($data['data']['spu_item'])));

        };

        

        if(!empty($label)){

            foreach ($label as $key => $value) {

                $label[$key] = [];

                $label[$key] = [

                    'relation_one'=>$data['data']['product_no'],

                    'relation_one_table'=>'product',

                    'relation_two'=>$value,

                    'relation_two_table'=>'label',

                ];

            }

            (new Relation())->saveAll($label);

        };

        return $data;



    }





    public static function dealGet($data)

    {   



        

            foreach ($data as $key => $value) {

                if($value['category_id']!=0){

                    $label = array();

                    array_push($label,$value['category_id']);
                    $label = array_keys(array_flip($label) + array_flip($value['sku_array'])+ array_flip($value['sku_item'])+ array_flip($value['spu_array'])+ array_flip($value['spu_item']));

                    $map['id'] = ['in',$label];

                    $res = resDeal((new label())->where($map)->select());

                    $res = clist_to_tree($res);

                    $res = changeIndexArray('id',$res);

                    $data[$key]['label'] = $res;

                };

            }; 

        

               

        return $data;

        

    }







    public static function dealUpdate($data)

    {   

        if(isset($data['data'])&&(isset($data['data']['status'])||isset($data['data']['spu_item']))){

            

            $product = resDeal((new Product())->where($data['searchItem'])->select());



            if(isset($data['data']['status'])){

                $product_no = [];

                foreach ($product as $key => $value) {

                    array_push($product_no,$value['product_no']);

                    $res = (new Relation())->where('relation_one','=',$value['product_no'])->update(['status'=>$data['data']['status']]);

                };

                $res = (new Sku())->where('product_no','in',$product_no)->update(['status'=>$data['data']['status']]);

                

            };



            if(isset($data['data']['spu_item'])){



                $new_array = json_decode($data['data']['spu_item']);

                foreach ($product as $key => $value) {

                    $label = [];

                    

                    $map['relation_two'] = ['in',$value['spu_item']];

                    $map['relation_one'] = ['=',$value['product_no']];

                    

                    $res = (new Relation())->where($map)->delete();



                    foreach ($new_array as $c_key => $c_value) {

                        $label[$c_key] = [];

                        $label[$c_key] = [

                            'relation_one'=>$value['product_no'],

                            'relation_one_table'=>'product',

                            'relation_two'=>$c_value,

                            'relation_two_table'=>'label',

                        ];

                    };



                    (new Relation())->saveAll($label);

                    

                };

                

            };



            if(isset($data['data']['spu_array'])){



                $new_array = json_decode($data['data']['spu_item']);

                foreach ($product as $key => $value) {

                    $label = [];



                    $res = (new Relation())->where('relation_two','in',$value['spu_array'])->delete();

                    foreach ($new_array as $c_key => $c_value) {

                        $label[$c_key] = [];

                        $label[$c_key] = [

                            'relation_one'=>$value['product_no'],

                            'relation_one_table'=>'product',

                            'relation_two'=>$c_value,

                            'relation_two_table'=>'label',

                        ];

                    };

                    (new Relation())->saveAll($label);

                };

            };  

        }

        

        





        

    }







    public static function dealRealDelete($data)

    {   



    	$res = (new Product())->where($data['map'])->select();

        $id = [];

        $product_no = [];

        foreach ($res as $key => $value) {

            array_push($id,$value['id']);

            array_push($product_no,$value['id']);

        };

        (new Sku())->where('id','in',$id)->delete();

        (new Relation())->where('relation_one','in',$product_no)->delete();

        

    }





}