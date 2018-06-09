<?php
//
// AUTOLOAD CLASS
//
spl_autoload_register(function ($name) {
  $file = dir::get('oops').$name.'.php';
  if(is_file($file)) require_once($file);
});

//
// COMPOSER
//
setlocale(LC_ALL, 'ru_RU.UTF-8');
$composer = __DIR__ . '/../../../vendor/autoload.php';
if(is_file($composer)) require $composer;
unset($composer);


//
// URI
//
Class uri {

  public $uri = [];

  public function __construct(){
    $uri = $_SERVER['REQUEST_URI'];
    $uri = parse_url($uri)['path'];
    $uri = explode('/',$uri);
    foreach($uri as $key=>$val){ $val = trim($val); if(empty($val)) unset($uri[$key]); }
    $uri = array_values($uri);
    $this->uri = $uri;
  }

}

//
// PAGE
//
Class page {

  protected static $title = [];
  protected static $description = [];
  protected static $keywords = [];

  public function __construct($html){
$favicon = '
  <link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
  <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
  <link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
  <link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
  <link rel="manifest" href="/manifest.json">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">
';
    die('<!DOCTYPE html><html><head>'.$favicon.'<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta name="description" content="'.self::description().'"><meta name="keywords" content="'.self::keywords().'"><meta name="GENERATOR" content="livesugar"><link href="/favicon.ico" rel="shortcut icon"><title>'.self::title().'</title></head><body>'.$html.'</body></html>');
  }

  public static function title($title=null){
    if(!empty($title)) array_push(self::$title,$title);
    else return implode(' - ',array_reverse(self::$title));
  }
  public static function description($description=null){
    if(!empty($description)) array_push(self::$description,$description);
    else return implode(' ',array_reverse(self::$description));
  }
  public static function keywords($keywords=null){
    if(!empty($keywords)) array_push(self::$keywords,$keywords);
    else return implode(' ',array_reverse(self::$keywords));
  }

}


//
// REGISTER
//
Class register {

  protected static $register = [];

  public static function set($key,$val){
    self::$register[$key] = $val;
  }

  public static function get($key){
    if(isset(self::$register[$key])) return self::$register[$key];
    else return false;
  }

}

//
// APPS
//
Class apps {

  private static $save = [];
  private static $view = null;
  private static $apps = null;
  private static $live = false;
  private static $data = null;

  public function __construct(){

    if(!self::$apps = register::get('apps')) { self::$apps = $this; register::set('apps',self::$apps); }
    if(!self::$view = register::get('view')) { self::$view = new view; register::set('view',self::$view); }

  }

  public function __get($name){
    array_push(self::$save,$name);
    return new self;
  }

  public function __call($name,$value){

    # get name apps
    array_push(self::$save,$name);
    $name = implode('/',self::$save);
    self::$save = [];

    if(!$is = register::get('apps::'.$name)){

      # get file apps
      $file = dir::get('apps').$name.'.php';
      if(!is_file($file)) return null;

      # require function apps
      $function = require($file);
      if(!is_callable($function)) return null;

      # reflection
      $reflection = new \ReflectionFunction($function);
      $status = $reflection->getStaticVariables();

      # get reflection params
      if(!is_null(self::$data)){
        $params = [];
        foreach($reflection->getParameters() as $key=>$val){
          if(isset(self::$data[$val->name])) $params[] = self::$data[$val->name];
          else $params[] = null;
        }
      } else {
        $params = $value;
      }
      self::$data = null;


      # call function
      $function = call_user_func_array($function,$params);

      # is singleton
      if(isset($status['singleton']) && $status['singleton'] === true){
        register::set('apps::'.$name,$function);
      }

      # is public
      if(isset($status['public']) && $status['public'] === true){
        self::$live = true;
      }

      # return
      return $function;

    } else {

      return $is;

    } 

  }

  public function __invoke($name,$data){
    self::$data = $data;
    $res = (new self)->{$name}();
    if(self::$live === true) return $res;
    else return false;

  }

}

//
// VIEW
//
Class view {

      protected static $dir = [];
      protected static $save = [];
      protected static $apps = null;
      protected static $view = null;
      protected static $uri = [];
      public static $content = '';

      public function __construct(){

        if(!self::$apps = register::get('apps')) { self::$apps = new apps; register::set('apps',self::$apps); }
        if(!self::$view = register::get('view')) { self::$view = $this; register::set('view',self::$view); }

        self::$uri = (new uri)->uri;
      }

      public function __get($name){
        array_push(self::$save,$name);
        return (new self);
      }

      public function __call($name,$value){

        $content = '';

        //
        // MINIFY
        //
        $minify = function($buffer){
          if(!isset($_GET['minifyoff'])){
            //$buffer = preg_replace('#([^:])\/\/.*(\n?)$#m','$1',$buffer);
            $buffer = preg_replace('/\<\!\-\-[\s\S]*\-\-\>/ui',' ',$buffer);
            //$buffer = preg_replace('/[\s]+/ui',' ',$buffer);
            $buffer = preg_replace('/\>[\s]+\</ui','><',$buffer);
          }
          return $buffer;
        };
        array_push(self::$save,$name);
        $dir = dir::get('view').implode('/',self::$save);
        $view = $dir.'/index.phtml';
        $js = $dir.'/index.js';
        $css = $dir.'/index.css';
        $json = $dir.'/index.json';
        self::$save = [];

        // options
        if(is_file($json)) {
          $json = file_get_contents($json);
          $json = json_decode($json,true);

          // json js
          if(isset($json['js'])){
            foreach($json['js'] as $file){
              $file = dir::get('js').$file.'.js';
              if(is_file($file)){
                ob_start();
                echo '<script>';
                require_once($file);
                echo '</script>';
                $content .= ob_get_contents();
                ob_end_clean();
              }
            }
          }
          // json css
          if(isset($json['css'])){
            foreach($json['css'] as $file){
              $file = dir::get('css').$file.'.css';
              if(is_file($file)){
                ob_start();
                echo '<style>';
                require_once($file);
                echo '</style>';
                $content .= ob_get_contents();
                ob_end_clean();
              }
            }
          }
        }

        // load js
        if(is_file($js)) {
          ob_start();
          echo '<script>';
          require_once($js);
          echo '</script>';
          $content .= ob_get_contents();
          ob_end_clean();
        }

        // load css
        if(is_file($css)) {
          ob_start();
          echo '<style>';
          require_once($css);
          echo '</style>';
          $content .= ob_get_contents();
          ob_end_clean();
        }

        // load view html
        if(is_file($view)) {
          ob_start();
          require_once($view);
          $content .= ob_get_contents();
          ob_end_clean();
        } 


        # return content
        self::$content .= $minify($content);

      }

}

//
// Directories
//
class dir {

  protected static $register = [];

  public static function set($name,$value){
    self::$register[$name] = $value;
  }

  public static function get($name=false){
    if(empty($name)){
      return self::$register;
    } else {
      if(isset(self::$register[$name])) return self::$register[$name];
      else return false;
    }
  }

}

//
// File
//
class file {

  public static function find($hash){
    $way = str_split($hash);
    $name = array_pop($way);
    $way = implode('/',$way);
    $http = '/upload/'.$way.'/'.$name.'.file';
    $way = dir::get('upload').$way;
    $way = $way.'/'.$name.'.file';
    if(!is_file($way)) return false;
    return ['file'=>$way,'hash'=>$hash,'http'=>$http];
  }

  public static function upload(){
    $file = file_get_contents('php://input');
    $hash = hash('sha256',$file);
    $way = str_split($hash);
    $name = array_pop($way);
    $way = implode('/',$way);
    $http = '/upload/'.$way.'/'.$name.'.file';
    $way = dir::get('upload').$way;
    if(!is_dir($way)) mkdir($way,0755,true);
    $way = $way.'/'.$name.'.file';
    if(!is_file($way)){ file_put_contents($way,$file); }
    return ['file'=>$way,'hash'=>$hash,'http'=>$http];
  }

}


//
// ENGINE
//
(new class {

  public static $dir = [];
  public static $uri = [];
  public static $view = null;
  public static $apps = null;
  public static $page = [];
  public static $minify = null;

  public function __construct(){
    $memoryStart = memory_get_usage();
    $timeStart = microtime(1);

    dir::set('core',getcwd().'/');
    dir::set('apps',dir::get('core').'../apps/');
    dir::set('oops',dir::get('core').'../oops/');
    dir::set('road',dir::get('core').'../road/');
    dir::set('view',dir::get('core').'../view/');
    dir::set('css',dir::get('core').'css/');
    dir::set('js',dir::get('core').'js/');
    dir::set('img',dir::get('core').'img/');
    dir::set('font',dir::get('core').'font/');
    dir::set('upload',dir::get('core').'upload/');

    //
    // URI
    //
    self::$uri = (new uri)->uri;

    //
    // APPS
    //
    self::$apps = new apps;

    //
    // APPS
    //
    self::$view = new view;

    //
    // PAGE
    //
    self::$page['title'] = [];

    //
    // UPLOAD
    //
    if($_SERVER['CONTENT_TYPE'] == 'application/octet-stream'){
      $file = file::upload();
      header('Content-Type: application/json');
      die(json_encode($file,JSON_UNESCAPED_UNICODE));
    };

    //
    // LOAD IMAGE
    //
    if(isset(self::$uri[0]) && preg_match('/\.png$/Uui',self::$uri[0])){
      # uniq
      $uniq = substr(self::$uri[0],0,17);
      # width
      preg_match('/w\d+?/Uui',self::$uri[0],$width);
      if(isset($width[0]) && !empty($width[0])) $width = (int) substr($width[0],1);
      else $width = null;
      # height
      preg_match('/h\d+?/Uui',self::$uri[0],$height);
      if(isset($height[0]) && !empty($height[0])) $height = (int) substr($height[0],1);
      else $height = null;
      # resize
      $img = self::$apps->image->resize($uniq,$width,$height);
      if($img === false){
        header("HTTP/1.0 404 Not Found");
        exit;
      } else {
        # result
        header('Content-Type: image/png');
        die(file_get_contents($img));
      }
    };

    //
    // APPS EXEC
    //
    if($_SERVER['CONTENT_TYPE'] == 'application/json'){

      # get name apps
      $name = $_SERVER['REQUEST_URI'];
      $name = explode('/',$name);
      $name = array_filter($name,function($val,$key){
        $val = (string) trim($val);
        if(!empty($val)) return $val;
      },ARRAY_FILTER_USE_BOTH);
      $name = implode('/',$name);

      # get value apps
      $json = file_get_contents('php://input');
      $json = json_decode($json,true);
      if(!is_array($json)) die('{}');

      # exec apps
      $response = (new apps)($name,$json);
      if($response === false){
        header("HTTP/1.0 404 Not Found",false,404);
        exit;
      } else {
        header('Content-Type: application/json');
        die(json_encode($response,JSON_UNESCAPED_UNICODE));
      }
    }

    //
    // APPS EXEC FROM GET URL
    //
    if(isset($_GET['apps'])){
      $apps = $_GET['apps'];
      unset($_GET['apps']);
      $response = (new apps)($apps,$_GET);
      if($response === false){
        header("HTTP/1.0 404 Not Found",false,404);
        exit;
      } else {
        header('Content-Type: application/json');
        die(json_encode($response,JSON_UNESCAPED_UNICODE));
      }
    }

    //
    // VIEW EXEC FROM GET URL
    //
    if(isset($_GET['view'])){
      call_user_func_array([new view,$_GET['view']],[]);
      die(view::$content);
    }

    //
    // PAGE
    //
    header('TimeOfCompletion: '.round((microtime(1)-$timeStart),5,PHP_ROUND_HALF_EVEN).' sec');
    header('MemoryUsage: '.round(((memory_get_usage()-$memoryStart)/1024),5,PHP_ROUND_HALF_EVEN).' KiB');

    if(empty(self::$uri)) $page = ['index'];
    else $page = self::$uri;

    $isRoad = function($road){
      $file = dir::get('road').'/'.$road.'.json';
      if(is_file($file)) return true;
      else return false;
    };
    $openRoad = function($road){
      $file = dir::get('road').'/'.$road.'.json';
      if(!is_file($file)) return false;
      $file = file_get_contents($file);
      $open = json_decode($file,true);
      if(isset($open['view']) && is_array($open['view']) && !empty($open['view'])){
        ksort($open['view']);
        reset($open['view']);
      }
      return $open;
    };

    $isLink = function() use(&$page,&$isLink,$isRoad,$openRoad) {
      $link = implode('/',$page);
      $is = $isRoad($link);
      if($is !== false) { 
        return $openRoad($link); 
      } else {
        $page = array_slice($page,0,-1);
        if(empty($page)) return false;
        else return $isLink();
      }
    };

    $page = $isLink();

    if($page === false || !isset($page['view']) || empty($page['view'])) {
      header("HTTP/1.0 404 Not Found");
      die('<h1 style="font-size:5000%;margin:0px;padding:0px;text-align:center;line-height:63%;color:#eee;padding-top:8%;">404</h1>');
    }

    # title
    if(isset($page['title'])) page::title($page['title']);
    if(isset($page['description'])) page::description($page['description']);
    if(isset($page['keywords'])) page::keywords($page['keywords']);

    # view
    $view = $page['view'];
    if(!is_array($view)) $view = [$view];
    $split = [];
    foreach($view as $key=>$val){
      if(!empty($val)) $split[count(explode('/',$val))][] = $val;
    }
    krsort($split);


    # types
    if(!isset($page['type'])) $type = 'page';
    else $type = $page['type'];


    # create page
    while($spot = array_pop($split)){
      foreach($spot as $key=>$val){
        call_user_func_array([new view,$val],[]);
      }
    }

    switch($type){
      case "view":
        die(view::$content);
      break;
      case "page":
        (new page(view::$content));
      break;
    };

  }
});
?>
