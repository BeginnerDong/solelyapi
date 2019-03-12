<?php



namespace app\api\model;





use think\Model;

use app\api\model\User;

use app\api\model\OrderItem;

use app\api\model\Product;
use app\api\model\Sku;



class Order extends BaseModel

{



    public static function dealAdd($data)

    {   

        $standard = [
            'order_no'=>'',
            'parent_id'=>'',
            'pay'=>[],
            'price'=>'',
            'snap_address'=>[],
            'express'=>[],
            'pay_status'=>0,
            'type'=>'',
            'prepay_id'=>'',
            'wx_prepay_info'=>[],
            'order_step'=>0,
            'transport_status'=>0,
            'transaction_id'=>'',
            'refund_no'=>'',
            'isrefund'=>'',
            'create_time'=>time(),
            'invalid_time'=>'',
            'start_time'=>'',
            'end_time'=>'',
            'update_time'=>'',
            'finish_time'=>'',
            'delete_time'=>'',
            'passage1'=>'',
            'passage_array'=>[],
            'status'=>1,
            'thirdapp_id'=>'',
            'user_no'=>'',
            'user_type'=>'',
            'express_info'=>'',
            'payAfter'=>[],
            'standard'=>0,
            'discount'=>0,
            'balance'=>0,
            'group_no'=>'',
            'group_leader'=>'',
            'pay_no'=>'',
            'limit'=>'',
        ];

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

            $res = resDeal((new Order())->where($data['searchItem'])->select());

            foreach ($res as $key => $value) {

                $orderItemRes = (new OrderItem())->where([

                    'order_no' => $res[$key]['order_no'],

                    'status' => 1

                ])->select();

                foreach ($orderItemRes as $c_key => $c_value) {
                    (new OrderItem())->save(

                        ['status'  => -1],

                        ['id' => $c_value['id']]

                    );
                };

            };  

        };

        if(isset($data['data']['pay_status'])&&($data['data']['pay_status']==1||$data['data']['pay_status']==0)){

            $res = resDeal((new Order())->where($data['searchItem'])->select());
            
            foreach ($res as $key => $value) {

                $orderItemRes = (new OrderItem())->where([

                    'order_no' => $res[$key]['order_no'],

                    'status' => 1

                ])->select();

                foreach ($orderItemRes as $c_key => $c_value) {

                    if(!empty($c_value['product_id'])){
                        $c_res = (new Product())->where([
                            'id' => $c_value['product_id'],
                            'status' => 1
                        ])->find();
                    }else if(!empty($c_value['sku_id'])){
                        $c_res = (new Sku())->where([

                            'id' => $c_value['sku_id'],

                            'status' => 1

                        ])->find();
                    };
                    if($res){
                        
                        if($res[$key]['pay_status']==0&&$data['data']['pay_status']==1){
                            $sale_count = $c_res['sale_count']+$c_value['count'];
                            $stock = $c_res['stock']-$c_value['count'];
                            $has = true;
                        }else if($res[$key]['pay_status']==1&&$data['data']['pay_status']==0){
                            $sale_count = $c_res['sale_count']-$c_value['count'];
                            $stock = $c_res['stock']+$c_value['count'];
                            $has = true;
                        };

                        if(empty($res[$key]['group_no'])&&isset($has)){
                            $content = [
                                'sale_count'  => $sale_count,
                                'stock'  => $stock
                            ];
                        }else if(isset($has)){
                            $content = [
                                'sale_count'  => $sale_count,
                                'group_stock'  => $stock
                            ];
                        };
                        if(!empty($c_value['product_id'])&&isset($has)){
                            (new Product())->save($content,['id' => $c_res['id']]);
                        }else if(!empty($c_value['sku_id'])&&isset($has)){
                            (new Sku())->save($content,['id' => $c_res['id']]);
                        };
                    };
                    
                    (new OrderItem())->save(
                        ['pay_status' => $data['data']['pay_status']],
                        ['id' => $c_value['id']]
                    );

                };

                
                if(isset($data['data']['pay_status'])&&($data['data']['pay_status']==1)){

                    //同步切换子订单支付状态
                    $childOrders = resDeal((new Order())->where(['parentid' => $res[$key]['id'],])->select());

                    if (count($childOrders)>0) {
                       
                        foreach ($childOrders as $key => $value) {
                            
                            (new Order())->save(
                                ['pay_status'  => 1],
                                ['id' => $value['id']]
                            );
                        };
                    };

                    //检测团购订单
                    if (!empty($res[$key]['group_no'])) {
                        
                        $groupRes = resDeal((new Order())->where(['group_no' => $res[$key]['group_no'],'pay_status'=>1])->select());

                        if (count($groupRes)>0) {
                            
                            if (count($groupRes)<($groupRes[0]['standard']-1)) {
                                //未成团，无操作
                            }else{

                                $res = Order::where('group_no', $res[$key]['group_no'])->update(['order_step' => 5]);

                                $data['data']['order_step'] = 5;
                            };

                        }else if($res[$key]['standard']==1){

                            $data['data']['order_step'] = 5;

                        };

                    };
                };

            };
        };


        if(isset($data['data']['status'])&&$data['data']['status']==1){

            throw new ErrorMessage([

                'msg' => '请重新下单',

            ]);

        };
        return $data;

    }



    public static function dealRealDelete($data)

    {

    	$res = (new Order())->where($data['searchItem'])->select();

        foreach ($res as $key => $value) {

			OrderItem::destroy(['order_no' => $res[$key]['order_no']]);

        };

    }

}