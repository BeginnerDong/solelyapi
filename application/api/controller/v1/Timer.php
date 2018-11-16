<?php
/**
 * Created by 董博明.
 * User: 董博明
 * Date: 2018/5/20
 * Time: 18:14
 */

namespace app\api\controller\v1;

use think\Controller;
use app\api\model\Order;
use app\api\model\OrderItem;
use app\api\model\Product;

use app\lib\exception\SuccessMessage;
use app\lib\exception\ErrorMessage;

/**
 * 计时器
 */
class Timer extends Controller{



    public function index(){
        $this->checkExpireOrder()
    };

    //检查过期未支付订单
    public function checkExpireOrder(){
        $modelData = [];
        $modelData['searchItem'] = [
            'deadline'=>['<',time()],
            'status'=>1
        ];
        $res = (new Order())->where($modelData['searchItem'])->select();
        if($res){
            foreach ($res as $key => $value) {
                $orderItemRes = (new OrderItem())->where([
                    'order_no' => $value['order_no'],
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
                (new Order())->save(
                    ['status'  => -1],
                    ['id' => $value['id']]
                );
            };
        } 
    }
}