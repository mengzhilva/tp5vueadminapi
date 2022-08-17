<?php
namespace app\admin\controller;

class IndexController extends BaseController
{
	
    public function index()
    {
		return view('/base',['_view'=>'index']);
    }
  
}
