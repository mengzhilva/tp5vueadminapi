<?php
namespace mylib;


use PHPExcel_IOFactory;
use PHPExcel;
use PHPExcel_Worksheet;
use PHPExcel_Writer_Excel5;
use PHPExcel_Style_Alignment;

class myphpexcel{
	
	public static $daytime;
	public function __construct($daytime=''){
		
	}
	//导出设置顶部
	//参数title 导出文件的名称
	public static function setheader($title = '导出'){
		
		//导出
		$e = new \PHPExcel();
		
		$e->getProperties()->setCreator("ihb")
		->setLastModifiedBy("ihb")
		->setTitle($title)
		->setSubject($title)
		->setDescription("")
		->setKeywords($title)
		->setCategory("");
		return $e;
	}
	/*导出主体
	 * 参数：
	 * arr 表头的内容array("IDFA",'关键字','时间','手机版本','ip','渠道');
	 * list 格式array('sheet'=>'sheet','data'=>$lists);sheet工作表名称，data具体数据，应与arr内容对应
	 * e，导出对象
	 * */
	public static function setbody($list,$e,$arr){
		$clone = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P');
		//第一个sheetend
		foreach ($list as $ks=>$vs){
		
			$shee = $ks+1;
			$msgWorkSheet = new PHPExcel_Worksheet($e, $vs['sheet']); //创建一个工作表
			$e->addSheet($msgWorkSheet); //插入工作表
			$e->setActiveSheetIndex($shee); //切换到新创建的工作表
			$row = 1;
			foreach ($arr as $k=>$av){
				$e->getActiveSheet()->setCellValue($clone[$k].$row, $arr[$k]);
			}
			$hsize = 20;
			foreach ($vs['data'] as $kd=>$vd){
		
				$e->getActiveSheet()->getColumnDimension('A')->setWidth($hsize);
				$e->getActiveSheet()->getColumnDimension('B')->setWidth($hsize);
				$e->getActiveSheet()->getColumnDimension('C')->setWidth($hsize);
				$e->getActiveSheet()->getColumnDimension('D')->setWidth($hsize);
				$e->getActiveSheet()->getColumnDimension('E')->setWidth($hsize);
				$e->getActiveSheet()->getColumnDimension('F')->setWidth($hsize);
				$row = $kd+2;
				$dj = 0;
				$price = 0.00;
				
				foreach ($vd as $k=>$v){
					$e->getActiveSheet()->getStyle($clone[$k].$row)->getAlignment()->setWrapText(true);
					$e->getActiveSheet()->setCellValue($clone[$k].$row, $v);
		
				}
				foreach ($vd as $kk=>$vv){
					$e->getActiveSheet()->getStyle($clone[$kk].$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
					$e->getActiveSheet()->getStyle($clone[$kk].$row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				}
				$row++;
		
			}
		}
		return $e;
	}
	//导出输出
	//参数title 导出文件的名称
	public static function setfooter($e,$title='导出'){

		$w = new PHPExcel_Writer_Excel5($e);
		
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		$encoded_filename = urlencode($title);
		$ua = $_SERVER["HTTP_USER_AGENT"];
		if (preg_match("/MSIE/", $ua)) {
			header('Content-Disposition: attachment; filename="' . $encoded_filename . '.xls"');
		} else if (preg_match("/Firefox/", $ua)) {
			header('Content-Disposition: attachment; filename*="utf8\'\'' . $title . '.xls"');
		} else {
			header('Content-Disposition: attachment; filename="' . $title . '.xls"');
		}
		
		header("Content-Transfer-Encoding:binary");
		$w->save('php://output');
	}
	
}