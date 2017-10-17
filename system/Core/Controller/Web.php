<?php

/**
 * Web 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Web.php 7047 2013-11-25 02:51:14Z jiangjian $
 */

abstract class Core_Controller_Web extends Core_Controller_Abstract
{   
    /**
     * 自动加载视图
     *
     * @var bool
     */
    public $yafAutoRender = true;

    /**
     * 传出模板变量
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function assign($key, $value = null)
    {
        return $this->_view->assign($key, $value);
    }

    /**
     * 设置模板布局
     * @author wangyaojun
     * @param string $layout
     */
    public function setLayout($layout = null){   
        if($layout){
            Yaf_Registry::set('layout',$layout.'.phtml');
        }else{
            Yaf_Registry::set('layout','');
        }
        
    }

    public function cancelLayout(){
        Yaf_Dispatcher::getInstance()->autoRender(FALSE);
    }
    /**
     * 是否自动渲染视图文件
     *
     * @param bool $bool
     */
    public function autoRender($bool = true)
    {
        $this->yafAutoRender = (bool) $bool;
    }

    public function alert($msg, $resultType = 'success', $url = '', $extra = '')
    {
        if (is_array($msg)) {
            $msg = implode('\n', $msg);
        }

        // Ajax
        if ($this->isAjax()) {
            $this->jsonx($msg, $resultType);
        }

        // 跳转链接
        if ($url == 'halt') {
            $jumpStr = '';
        } else {
            $url = $url ? $url : $this->refer();
            $url = $url ? $url : '/';
            $jumpStr = $url ? "top.location.href = '{$url}';" : '';
        }

        $this->js("top.alert('{$msg}'); {$extra} {$jumpStr}");
    }

    public function js($script, $exit = true)
    {
        echo('<script type="text/javascript">' . $script . '</script>');
        $exit && exit();
    }

    public function jump($url = '')
    {
        $url = $url ?: $this->refer();
        $this->js('top.location.href = \'' . $url . '\';');
    }

    public function refer()
    {
        return $this->getx('refer') ?: (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
    }
}