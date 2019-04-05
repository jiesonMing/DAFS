<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Validate;
use think\Session;
use think\Db;

class Base extends Controller
{
    public function _initialize()
    {
        $this->req = Request::instance();
        $action = $this->req->action();
        $this->assign('nav', $action);
    }
}