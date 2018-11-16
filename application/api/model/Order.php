<?php



namespace app\api\model;





use think\Model;

use app\api\model\User;

use app\api\model\OrderItem;

use app\api\model\Product;



class Order extends BaseModel

{



    public static function dealAdd($data)

    {   

        $standard = ['order_no'=>'','pay'=>[],'price'=>'','snap_address'=>[],'express'=>[],'pay_status'=>0,'type'=>'','prepay_id'=>'','wx_prepay_info'=>[],'order_step'=>0,'transport_status'=>0,'transaction_id'=>'','refund_no'=>'','isrefund'=>'','create_time'=>time(),'invalid_time'=>'','start_time'=>'','end_time'=>'','update_time'=>'','finish_time'=>'','delete_time'=>'','passage1'=>'','passage2'=>'','passage_array'=>[],'status'=>1,'thirdapp_id'=>1,'user_no'=>'','user_type'=>'','standard'=>0,'deadLine'=>0];



        if(isset($data['data']['user_no'])){

            $res = User::get(['user_no' => $data['data']['user_no']]);

            if($res){

                $data['data']['user_type'] = $res['user_type'];

            }else{

                throw new ErrorMessage([

                    'msg' => '关联user信息有误',

                ]);

            };

        };

        

        $data['data'] = chargeBlank($standard,$data['data']);

        return $data;

        

    }





    public static function dealGet($data)

    {   



        foreach ($data as $key => $value) {

            $value = resDeal([$value])[0];

            $orderItems = resDeal(OrderItem::all(['order_no' => $value['order_no']]));

            $data[$key]['products'] =  $orderItems;

        };

        return $data;

        

    }



    public static function dealUpdate($data)

    {   

        if(isset($data['data']['status'])&&$data['data']['status']==-1){

            $res = (new Order())->where($data['searchItem'])->select();

            foreach ($res as $key => $value) {

                $orderItemRes = (new OrderItem())->where([

                    'order_no' => $res[$key]['order_no'],

                    'status' => 1

                ])->select();

                foreach ($orderItemRes as $c_key => $c_value) {

                    $productRes = (new Product())->where([

                        'id' => $c_value['product_id'],

                        'status' => 1

                    ])->find();

                    if($productRes){

                        (new Product())->save(

                            ['stock'  => $productRes['stock']+$c_value['count']],

                            ['id' => $productRes['id']]

                        );



                    };

                    (new OrderItem())->save(

                        ['status'  => -1],

                        ['id' => $c_value['id']]

                    );

                };

            };  

        };



        if(isset($data['data']['status'])&&$data['data']['status']==1){

            throw new ErrorMessage([

                'msg' => '请重新下单',

            ]);

        };



    }



    public static function dealRealDelete($data)

    {   



    	$res = (new Order())->where($data['searchItem'])->select();

        foreach ($res as $key => $value) {

			OrderItem::destroy(['order_no' => $res[$key]['order_no']]);

        };

        

    }





}