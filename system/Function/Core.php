<?php

/**
 * 核心函数库（修改请谨慎）
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Core.php 10913 2014-05-08 06:01:09Z jiangjian $
 */

/**
 * 当前开发环境判断
 *
 * @param string $env devel/product
 * @return bool
 */
function isEnv($env)
{
    return $env == Yaf_Registry::get('config')->application->system->environ;
}

/**
 * 单例加载
 *
 * @param string $className
 * @return object
 */
function S($className)
{
    return Core_Loader::getSingleton($className);
}

/**
 * 常用组件工厂
 *
 * @param string $component
 * @return object
 */
function F($component)
{
    if (! isset($GLOBALS['__G_' . $component])) {
        switch ($component) {
            case 'Session':
                $GLOBALS['__G_Session'] = Yaf_Session::getInstance();
                break;
            case 'Cookie':
                $GLOBALS['__G_Cookie'] = Com_Cookie::getInstance();
                break;
            case 'Memcache':
                $GLOBALS['__G_Memcache'] = Com_Cache::getInstance('Memcache');
                break;
            case 'Redis':
                $GLOBALS['__G_Redis'] = Com_Cache::getInstance('Redis');
                break;
            case 'MemLock':
                $GLOBALS['__G_MemLock'] = new Com_Lock(F('Memcache'));
                break;
        }
    }

    return $GLOBALS['__G_' . $component];
}

/**
 * 加载模型
 *
 * @param string $name
 * @return object
 */
function Model($name)
{
    return S('Model_' . $name);
}

/**
 * 加载 Dao
 *
 * @param string $name
 * @return object
 */
function Dao($name)
{
    return S('Dao_' . $name);
}

/**
 * 国际化文本显示
 *
 * @param string $string
 * @param array $vars
 * @return string
 */
function __($string, $vars = null)
{
    if (! $vars) {
       return _($string);
    }

    $searchs = $replaces = array();

    foreach ((array) $vars as $key => $var) {
        $searchs[] = '{' . $key . '}';
        $replaces[] = $var;
    }

    return str_replace($searchs, $replaces, _($string));
}

 // 不需要国际化
if (! function_exists('_')) {
    function _($string)
    {
        return $string;
    }
}

/**
 * 仅供 PoEdit 扫描搜集
 *
 * @param $string
 * @return $string
 */
function __N($string)
{
    return $string;
}

/**
 * 包含模板
 *
 * @param string $tpl
 * @return string
 */
function template($tpl)
{
    return rtrim(Core_View::getInstance()->getScriptPath(), DS) . DS . $tpl . TPL_EXT;
}

/**
 * new add wangyaojun
 * 直接返回页面 可带参数
 * @param string $tpl
 * @param type $data
 * @return type
 */
function templateWithData($tpl,$data=array())
{   
    $tpl = rtrim(Core_View::getInstance()->getScriptPath(), DS) . DS . $tpl;
    //echo Core_View::getInstance()->display($tpl, $data);
    //die;
    Core_View::getInstance()->display($tpl, $data);
}


/**
 * 抛异常
 *
 * @param string $msg
 * @param string $class
 * @param string $errType
 * @throws Core_Exception_Abstract
 * @return void
 */
function throws($msg, $class = 'Logic', $errType = null)
{
    $class = 'Core_Exception_' . ucfirst($class);

    $e = new $class($msg);
    $errType && $e->setErrType($errType);

    throw($e);
}

/**
 * 抛异常
 *
 * @param string $msg
 * @throws Core_Exception_Abstract
 * @return void
 */
function throws403($msg)
{
    throw new Core_Exception_403($msg);
}

/**
 * 403
 *
 * @return void
 */
function header403()
{
    header('HTTP/1.0 403 Forbidden');
    header('Status: 403 Forbidden');
    exit('403 Forbidden');
}

/**
 * 404
 *
 * @return void
 */
function header404()
{
    header('HTTP/1.0 404 Not Found');
    header('Status: 404 Not Found');
    exit('404 Not Found');
}

/**
 * 500
 *
 * @return void
 */
function header500()
{
    header('HTTP/1.0 500 Internal Server Error');
    header('Status: 500 Internal Server Error');
    exit('500 Internal Server Error');
}

/**
 * 遍历 addslashes
 *
 * @param mixed $data
 * @return mixed
 */
function saddslashes($data)
{
    return is_array($data) ? array_map(__FUNCTION__, $data) : addslashes($data);
}

/**
 * 遍历 stripslashes
 *
 * @param mixed $data
 * @return mixed
 */
function sstripslashes($data)
{
    return is_array($data) ? array_map(__FUNCTION__, $data) : stripslashes($data);
}

/**
 * 整形化
 *
 * @param bigint $num
 * @return bigint
 */
function xintval($num)
{
    return preg_match('/^\-?[0-9]+$/', $num) ? $num : 0;
}

/**
 * 浮点数
 *
 * @param int/float $val
 * @param int $precision
 * @return float
 */
function decimal($val, $precision = 0)
{
    if ((float) $val) {
        $val = round((float) $val, (int) $precision);
        list($a, $b) = explode('.', $val);
        if (strlen($b) < $precision) {
            $b = str_pad($b, $precision, '0', STR_PAD_RIGHT);
        }
        return $precision ? "$a.$b" : $a;
    }

    return $val;
}

/**
 * 逗号连接
 *
 * @param array $array
 * @return string
 */
function ximplode($array)
{
    return empty($array) ? 0 : "'" . implode("','", is_array($array) ? $array : array($array)) . "'";
}

/**
 * 逗号切开
 *
 * @param string $string
 * @return array
 */
function xexplode($string)
{
    return $string ? array_map('trim', explode(',', $string)) : array();
}

/**
 * 是否调试模式
 *
 * @return bool
 */
function isDebug()
{
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return true;
    }

    ! defined('DEBUG_XKEY') && define('DEBUG_XKEY', 'jiangjian');

    if (isset($_REQUEST['__debug']) && $_REQUEST['__debug'] == DEBUG_XKEY) {
        return true;
    }

    // PHP5.3 中 $_REQUEST 默认只含 GP 不含 $_COOKIE，所以需要另外读取
    if (isset($_COOKIE['__debug']) && $_COOKIE['__debug'] == DEBUG_XKEY) {
        return true;
    }

    return false;
}

/**
 * 数组转为对象
 *
 * @param array $e
 * @return object
 */
function arrayToObject($e)
{
    if (! is_array($e)) {
        return $e;
    }

    return (object) array_map(__FUNCTION__, $e);
}

/**
 * 对象转为数组
 *
 * @param object $e
 * @return array
 */
function objectToArray($e)
{
    if (is_object($e)) {
        $e = (array) $e;
    }

    if (! is_array($e)) {
        return $e;
    }

    return array_map(__FUNCTION__, $e);
}

/**
 * 将 Core_Model_ArrayAcceess 的模型实例转为数组
 *
 * @param mixed $model
 * @return array
 */
function modelToArray($model)
{
    if ($model instanceof Core_Model_Abstract) {
        $model = $model->__toArray();
    }

    if (! is_array($model) || ! $model) {
        return $model;
    }

    return array_map(__FUNCTION__, $model);
}

/**
 * 给某个日期增加N秒
 * 作用：对DB中的timestamp字段类型进行累加、累减
 *
 * @param string $date 2013-02-21 13:19:00
 * @param int $offset 秒数
 * @return string $date
 */
function incrDate($date, $addSecs)
{
    return date('Y-m-d H:i:s', strtotime($date) + $addSecs);
}

function incrDateFromNow($addSecs)
{
    return date('Y-m-d H:i:s', $GLOBALS['_TIME'] + $addSecs);
}

function timeToDate($time)
{
    return date('Y-m-d H:i:s', $time);
}

function _exit($msg)
{
    exit('<p style="background: #333; color: #FFF; font-size: 24px; padding: 12px;">' . $msg . ' [<a style="color: #FFF" href="javascript:;" onclick="window.history.back()">返回上页</a>]</p>');
}

/**
 * var_dump 的封装
 *
 * @param mixed $s
 * @param bool $exit
 * @return void
 */
function vd($s, $exit = true)
{
    echo '<pre>';
    var_dump($s);
    echo '</pre>';
    $exit && exit();
}

/**
 * print_r 的封装
 *
 * @param mixed $s
 * @param bool $exit
 * @return void
 */
function pr($s, $exit = true)
{
    echo '<pre>';
    print_r($s);
    echo '</pre>';
    $exit && exit();
}

/**
 * 交换两个变量的值
 *
 * @param mixed &$first
 * @param mixed &$second
 * @return void
 */
function swap(&$first, &$second)
{
    $temp = $first;
    $first = $second;
    $second = $temp;
}
function pidToCode($pid){
  $sssssdir = "PO0J1K2L3X4D5A6E7F8B9CDI";
  $result='';
  $app='';
  $pid=(string)$pid;
  for ($i = 0; $i < strlen($pid); ++$i){
    $c = $pid[$i];
    if (ord($c) >=ord(0) && ord($c) <= ord(9)){
		
      $result .= $sssssdir[ord($c) - ord(0)];
      $app .= $sssssdir[10 + ord($c) - ord(0)];
    }
  }

  $result .= $app;

  if (strlen($result) < 10){
    $result .=substr($sssssdir,10);
  }
  return strtoupper(substr($result,0,8));
}

function codeToPid($str){
    $str=strtoupper($str);
    $pid='';
    $code='PO0J1K2L3X4D5A6E7F8B9CDI';
    for ($i = 0; $i < strlen($str); ++$i){
      $c = $str[$i];
      for ($j = 0; $j<strlen($code); ++$j){
        if ($c == $code[$j]){
          if ($j <= 9){
            $pid.=$j;
          }
          break;
        }
      }
    }
    if (strlen($pid)==0){
      return 0;
    }
    return intval($pid);
}

function isValidUserCode($user_code) 
{
  if ((strlen($user_code) != 8 ) || (pidToCode(codeToPid($user_code)) != strtoupper($user_code))) 
  {
    return false;
  }
  
  return true;
}

 function P($result){
   exit(print_r($result,true));
 }
 
 function checkCode($str){
   $code=array('0','1','2','3','4','5','6','7','8','9','A','B',
   'C','D','E','F','G','H','I','J','K','L','M','N','O','P',
   'Q','R','S','T','U','V','W','X','Y','Z');
   if(!empty($str)){
      $len=count($str);
      for($i=0;$i<$len;$i++)
      {
         if(!in_array($str[$i],$code)){
            return false;
         }
      }
      return true;
   }else{
      return false;
   }
 }
 function checkValue($value){
   if(is_numeric($value)){
       if($value>PHP_INT_MAX){
          return false;
       }else{
         return true;
       }  
   }else{
     return false;
   }
 }
 function guid(){
    if (function_exists('com_create_guid')){
      $str=com_create_guid();
      return substr($str, 1, strlen($str)-2);
    }else{
      mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
      $charid = strtoupper(md5(uniqid(rand(), true)));
      $hyphen = chr(45);// "-"
      $uuid = substr($charid, 0, 8).$hyphen
      .substr($charid, 8, 4).$hyphen
      .substr($charid,12, 4).$hyphen
      .substr($charid,16, 4).$hyphen
      .substr($charid,20,12);
      return $uuid;
    }
  }
  
  
  
  