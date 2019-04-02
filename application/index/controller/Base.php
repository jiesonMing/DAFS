<?php
namespace app\index\controller;
use think\Controller;
use think\Request;

class Base extends Controller
{
    public function _initialize()
    {
        $this->req = Request::instance();
        $action = $this->req->action();
        $this->assign('nav', $action);
    }
}