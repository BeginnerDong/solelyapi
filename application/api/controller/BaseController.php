<?php
namespace app\api\controller;
use app\api\service\Token;
use think\Controller;
use app\api\controller\v1\Admin as AdminController;
use app\api\model\Auth as AuthModel;
use app\api\model\ThirdApp as ThirdAppModel;
use think\Request as Request;
use app\api\service\base\Admin as AdminService;
use think\Cache;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\SuccessMessage;
use app\lib\exception\TokenException;
use think\Loader;



class BaseController extends Controller{
	//CMS端请求接口前的统一验证//控制器继承base的都是cms端操作
    public function _initialize(){
        
    }

    // protected function checkExclusiveScope(){
    //     Token::needExclusiveScope();
    // }
    // protected function checkPrimaryScope(){
    //     Token::needPrimaryScope();
    // }
    // protected function checkSuperScope(){
    //     Token::needSuperScope();
    // }
    
    /**
     * @param array 接收到的数据
     * @return 检查id参数是否设置
     */
    protected function checkID()
    {
        $data=Request::instance()->param();
        if (!isset($data['id'])) {
            throw new TokenException([
                'msg'=>'缺少参数ID',
                'solelyCode'=>200001
            ]);
        }
    }

    /**
     * @param array 接收到的数据
     * @return 检查id参数是否设置
     */
    protected function checkPrimary()
    {
        $data=Request::instance()->param();
        $admininfo = Cache::get('info'.$data['token']);
        if ($admininfo['primary_scope']<30) {
            throw new TokenException([
                'msg'=>'权限不足',
                'solelyCode'=>200013
            ]);
        }
    }

    /*Excel导出*/
    public function exportExcel($expTitle,$expCellName,$expTableData,$fileName){ 
        Loader::import('phpexcel.PHPExcel',EXTEND_PATH,'.php');
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
        Loader::import('phpexcel.PHPExcel.IOFactory'); 
        $objWriter=\PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007'); 
        $objWriter->save('php://output');
        exit;     
    }

}