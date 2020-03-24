# solely项目数据库字典

### 目录

- 功能概述
- 数据对照表


---

**1\. 功能概述**

&emsp;&emsp;项目主要功能包括：
包含权限管理、用户管理、菜单管理、文章管理、商品管理、订单管理等基本模块；
支持公众号文章获取、公众号菜单编辑、小程序模板消息等微信功能；
支持微信支付、支付宝支付；
支持七牛云在线图片管理；

---
**2\. 数据对照表**

### 通用字段说明

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ | 
| id | int(11)| 主键：该数据ID |
| listorder | int(11) | 自定义排序 |
| img_array | varchar(100) | 图片组 |
| create_time | int(11) | 创建时间 |
| update_time | int(11) | 更新时间 |
| delete_time | int(11) | 删除时间 |
| thirdapp_id | int(11) | 关联thirdapp |
| user_no | varchar(255) | 关联user |
| user_type | tinyint(2) | 用户类型0.前端2.cms |
| status | tinyint(2) | 状态:1正常；-1删除 |



### user表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ | 
| login_name | varchar(100) | 登录名 |
| password | varchar(255) | 密码 |
| nickname | varchar(255) | 微信昵称 |
| openid | varchar(50) | 微信openid |
| headImgUrl | varchar(9999) | 微信头像 |
| role | int(11) | 权限角色 |
| primary_scope | int(11) | 权限级别：90.平台管理员;60.项目管理员;30管理员;10用户 |
| user_type | tinyint(2) | 0,小程序用户;2,cms用户; |
| user_no | varchar(255) | 用户编号 |



### user_info表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ |
| name | varchar(255) | 名称 |
| gender | tinyint(2) | 性别:1.男;2.女 |
| address | varchar(255) | 地址 |
| phone | varchar(255) | 电话 |
| score | decimal(10,2) | 积分 |
| balance | decimal(10,2) | 余额 |



### label表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------  | 
| title | varchar(40) | 菜单名称 |
| description| varchar(255) | 描述 |
| parentid| int(11) | 父级菜单ID |
| type | tinyint(2) |  1,menu;2,menu_item;3.category;4.coupon;5.sku;6.sku_item |



### article表

| 字段 | 类型 | 说明 |
| ------ |  :------:  | ------  | 
| title | varchar(100) | 文章标题 |
| menu_id | int(11) | 关联label表 |
| description | varchar(255) | 描述 |
| content | text | 文章内容 |
| mainImg | text | 文章主图 |



### message表-留言(type=1)

| 字段 | 类型 | 说明 |
| ------ |  :------:  | ------  | 
| title | varchar(255) | 标题 |
| description | varchar(255) | 描述 |
| content | text | 内容 |
| mainImg | text | 主图，一般在列表渲染 |



### log表

| 字段 | 类型 | 说明 |
| ------ |  :------:  | ------  | 
| type | int(11) | 类别:1.点赞;2.收藏;3.签到 |
| relation_table | varchar(100) | 关联表 |
| relation_id | varchar(100) | 关联信息 |
| relation_user | varchar(255) | 关联用户 |



### pay_log表

| 字段 | 类型 | 说明 |
| ------ |  :------:  | ------  | 
| title | varchar(255) | 标题 |
| result | varchar(255) | 结果描述 |
| content | text | 详情 |
| type | int(11) | 类别:1.微信支付 |
| order_no | varchar(100) | 关联order |
| pay_no | varchar(255) | 关联flowLog |
| transaction_id | varchar(255) | 微信流水 |
| behavior | int(11) | 预留 |
| pay_info | varchar(999) | 支付信息 |
| prepay_id | varchar(255) | 订单微信支付的预订单id(用于发送模板消息) |
| wx_prepay_info | varchar(999) | 储存微信预支付信息，再次调起支付使用 |
| parent_no | varchar(255) | 父级订单NO |



### product表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ | 
| product_no | varchar(255) | NO |
| title | varchar(255) | 商品名称 |
| description | varchar(255) | 描述 |
| content | text | 详情 |
| mainImg | text | 主图 |
| bannerImg | text | banner图 |
| category_id | int(11) | 关联label表 |
| type | int(11) | 1.普通商品 |
| price | decimal(10,2) | 价格 |
| o_price | decimal(10,2) | 原价 |
| group_price | decimal(10,2) | 团购价格 |
| stock | int(11) | 标准库存 |
| sale_count | int(11) | 销量 |
| start_time | bigint(13) | 开启时间 |
| end_time | bigint(13) | 结束时间 |
| on_shelf | tinyint(2) | 1.上架-1.下架 |
| is_date | tinyint(2) | 1.日期库存0.非日期库存 |
| duration | bigint(13) | 有效期 |



### sku表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ | 
| sku_no | varchar(255) | NO |
| product_no | varchar(255) | 关联product表 |
| title | varchar(255) | 商品名称 |
| price | decimal(10,2) | 价格 |
| group_price | decimal(10,2) | 团购价格 |
| o_price | decimal(10,2) | 原价 |
| stock | int(11) | 标准库存 |
| sale_count | int(11) | 销量 |
| on_shelf | tinyint(2) | 1.上架-1.下架 |
| is_date | tinyint(2) | 1.日期库存0.非日期库存 |



### product_date表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ |
| type | tinyint(2) | 1.标准库存2.日期库存 |
| product_no | varchar(255) | 商品NO |
| sku_no | varchar(255) | SKU NO |
| price | decimal(10,2) | 价格 |
| group_price | decimal(10,2) | 团购价格 |
| day_time | int(11) | 0点时间戳 |
| stock | int(11) | 库存 |
| group_stock | int(11) | 团购库存 |



### order表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ | 
| order_no | varchar(255) | 订单NO |
| pay | varchar(255) | pay方式详情 |
| price | decimal(10,2) | 订单金额 |
| pay_status | tinyint(2) | 0.未支付1.已支付 |
| type | tinyint(2) | 1.普通商品,2.会员卡,3.团购商品,6.虚拟订单 |
| order_step | tinyint(2) | 0.正常下单,1.申请撤单,2.完成撤单,3.完结 |
| group_status | tinyint(2) | 0.未成团,1.成团 |
| transport_status | tinyint(2) | 0.未发货；1.配送中；2.已收货 |
| level | tinyint(2) | 层级：1.父级订单 |
| parent_no | varchar(255) | 父级订单NO |
| product_id | int(11) | 商品id |
| sku_id | int(11) | SKU id |
| title | varchar(255) | 商品名称 |
| unit_price | decimal(10,2) | 商品单价 |
| count | int(11) | 商品数量 |
| isremark | tinyint(2) | 0.未评论；1.已评论 |
| index | int(11) | 序号 |



### order_item表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ | 
| order_no | varchar(255) | 订单NO |
| product_id | int(11) | 商品id |
| snap_product | text | 商品信息快照 |



### flow_log表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ | 
| type | int(11) | 1.微信支付2.余额支付3.积分支付 |
| count | int(11) | 金额 |
| trade_info | varchar(255) | 说明 |
| order_no | varchar(255) | 订单NO |
| parent_no | varchar(255) | 父级订单NO |
| level | tinyint(2) | 层级 |
| account | tinyint(2) | 0.不计算1.计算 |
| withdraw | tinyint(2) | 0.非提现1.提现 |
| withdraw_status | tinyint(2) | -1.拒绝0.待审核1.同意 |



### coupon表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ | 
| coupon_no | varchar(255) | 优惠券编号 |
| title | varchar(255) | 标题 |
| description | varchar(255) | 描述 |
| content | text | 详情 |
| mainImg | text | 主图 |
| bannerImg | text | 轮播图 |
| price | decimal(10,2) | 价格 |
| score | int(11) | 最高可使用积分 |
| value | int(11) | 价值，可抵扣金额 |
| discount | int(11) | 折扣百分比，默认100，即无折扣 |
| condition | int(11) | 使用条件，满减要求 |
| stock | int(11) | 库存 |
| sale_count | int(11) | 销量 |
| type | int(11) | 1.抵扣券2.折扣券 |



### user_coupon表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ | 
| type | tinyint(2) | 1.抵扣券2.折扣券 |
| use_step | tinyint(2) | 1.未使用2.已使用-1.已过期 |
| invalid_time | bigint(13) | 过期时间戳，前端记录13位 |



### wx_template表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ |
| name | varchar(50) | 模板名称 |
| template_no | varchar(100) | 模板号 |



### role表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ |
| name | varchar(50) | 角色名称 |



### auth表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ |
| role | int(11) | 所属角色ID |
| path | varchar(100) | 权限路径(对应CMS设置) |



### visitor_logs表

| 字段 | 类型 | 说明 |
| ------ | ------  | ------ |
| ip | varchar(50) | IP记录 |
| origin | varchar(50) | 来源 |
| content | varchar(500) | 访客记录 |
| type | tinyint(2) | 1.标签2.记录 |
| count | int(11) | 计数 |
| cid | int(11) | 地区代码 |
| device | varchar(255) | 设备 |
| address | varchar(255) | 地址 |
| longitude | varchar(255) | 经度 |
| latitude | varchar(255) | 纬度 |
| visitor_url | varchar(255) | 访问页面 |

---