<?php

//
// RAW POST
//
define('RAW_POST', file_get_contents('php://input'));

//
// AUTOLOAD CLASS
//
spl_autoload_register(function ($name) {
    $file = dir::get('oops').$name.'.php';
    $file = str_replace('\\','/',$file);
    if (is_file($file)) {
        require_once $file;
    }
});

//
// COMPOSER
//
setlocale(LC_ALL, 'ru_RU.UTF-8');
$composer = __DIR__.'/../../../vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
}
unset($composer);

//
// URI
//
class uri
{
    public $uri = [];

    public function __construct()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            $this->uri = false;

            return false;
        }

        $uri = $_SERVER['REQUEST_URI'];
        $uri = parse_url($uri)['path'];
        $uri = explode('/', $uri);
        foreach ($uri as $key=> $val) {
            $val = trim($val);
            if (empty($val)) {
                unset($uri[$key]);
            }
        }
        $uri = array_values($uri);
        $this->uri = $uri;
    }
}

//
// PAGE
//
class page
{
    protected static $title = [];
    protected static $description = [];
    protected static $keywords = [];

    public function __construct($html)
    {
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
      die('<!DOCTYPE html><html><head>'.$favicon.'<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta name="description" content="'.self::description().'"><meta name="keywords" content="'.self::keywords().'"><link href="/favicon.ico" rel="shortcut icon"><title>'.self::title().'</title></head><body>'.$html.'</body></html>');
    }

    public static function title($title = null)
    {
        if (!empty($title)) {
            array_push(self::$title, $title);
        } else {
            return implode(' - ', array_reverse(self::$title));
        }
    }

    public static function description($description = null)
    {
        if (!empty($description)) {
            array_push(self::$description, $description);
        } else {
            return implode(' ', array_reverse(self::$description));
        }
    }

    public static function keywords($keywords = null)
    {
        if (!empty($keywords)) {
            array_push(self::$keywords, $keywords);
        } else {
            return implode(' ', array_reverse(self::$keywords));
        }
    }
}

//
// REGISTER
//
class register
{
    protected static $register = [];

    public static function set($key, $val)
    {
        self::$register[$key] = $val;
    }

    public static function get($key)
    {
        if (isset(self::$register[$key])) {
            return self::$register[$key];
        } else {
            return false;
        }
    }
}

//
// APPS
//
class apps
{
    private static $save = [];
    private static $view = null;
    private static $apps = null;
    private static $live = false;
    private static $data = null;
    private static $type = null;

    public function __construct()
    {
        if (!self::$apps = register::get('apps')) {
            self::$apps = $this;
            register::set('apps', self::$apps);
        }

        if (!self::$view = register::get('view')) {
            self::$view = new view();
            register::set('view', self::$view);
        }

    }

    public function __get($name)
    {
        array_push(self::$save, $name);

        return new self();
    }

    public function __call($name, $value)
    {


    // get name apps
        array_push(self::$save, $name);
        $name = implode('/', self::$save);
        self::$save = [];

        if (!$is = register::get('apps::'.$name)) {

      // get file apps
            $file = dir::get('apps').$name.'.php';
            if (!is_file($file)) {
                return;
            }

            // require function apps
            $function = require $file;
            if (!is_callable($function)) {
                return;
            }

            // reflection
            $reflection = new \ReflectionFunction($function);
            $status = $reflection->getStaticVariables();

            // get reflection params
            if (!is_null(self::$data)) {
                $params = [];
                foreach ($reflection->getParameters() as $key=>$val) {
                    if (isset(self::$data[$val->name])) {
                        $params[] = self::$data[$val->name];
                    } else {
                        $params[] = null;
                    }
                }
            } else {
                $params = $value;
            }

            self::$data = null;

            if(self::$type == 'http' && (!isset($status['public']) || $status['public'] !== true)) { return null; }

            self::$type = null;

            // call function
            $function = call_user_func_array($function, $params);

            // is singleton
            if (isset($status['singleton']) && $status['singleton'] === true) {
                register::set('apps::'.$name, $function);
            }

            // is public
            if (isset($status['public']) && $status['public'] === true) {
                self::$live = true;
            } else {
                self::$live = false;
            }

            // return
            return $function;
        } else {
            return $is;
        }
    }

    public function __invoke($name, $data, $type=null)
    {
        self::$data = $data;
        self::$type = $type;
        $res = (new self())->{$name}();

        if (self::$live === true || $res === null) {
            return $res;
        } else {
            return false;
        }
    }

}

//
// VIEW
//
class view
{
    protected static $dir = [];
    protected static $save = [];
    protected static $apps = null;
    protected static $view = null;
    protected static $uri = [];
    public static $contentHtml = '';
    public static $contentJs = '';
    public static $contentCss = '';

    public function __construct()
    {
        if (!self::$apps = register::get('apps')) {
            self::$apps = new apps();
            register::set('apps', self::$apps);
        }
        if (!self::$view = register::get('view')) {
            self::$view = $this;
            register::set('view', self::$view);
        }

        self::$uri = (new uri())->uri;
    }

    public function __get($name)
    {
        array_push(self::$save, $name);

        return new self();
    }

    public function __call($name, $value)
    {
        $contentHtml = null;
        $contentJs = null;
        $contentCss = null;

        array_push(self::$save, $name);
        $dir = dir::get('view').implode('/', self::$save);
        $view = $dir.'/index.phtml';
        $js = $dir.'/index.js';
        $css = $dir.'/index.css';
        $json = $dir.'/index.json';
        self::$save = [];

        // options
        if (is_file($json)) {
            $json = file_get_contents($json);
            $json = json_decode($json, true);

            // json js
            if (isset($json['js'])) {
                foreach ($json['js'] as $file) {
                    $file = dir::get('js').$file.'.js';
                    if (is_file($file)) {
                        ob_start();
                        require_once $file;
                        $contentJs .= ob_get_contents();
                        ob_end_clean();
                    }
                }
            }
            // json css
            if (isset($json['css'])) {
                foreach ($json['css'] as $file) {
                    $file = dir::get('css').$file.'.css';
                    if (is_file($file)) {
                        ob_start();
                        require_once $file;
                        $contentCss .= ob_get_contents();
                        ob_end_clean();
                    }
                }
            }
        }

        // load js
        if (is_file($js)) {
            ob_start();
            require_once $js;
            $contentJs .= ob_get_contents();
            ob_end_clean();
        }

        // load css
        if (is_file($css)) {
            ob_start();
            require_once $css;
            $contentCss .= ob_get_contents();
            ob_end_clean();
        }

        // load view html
        if (is_file($view)) {
            ob_start();
            require_once $view;
            $contentHtml .= ob_get_contents();
            ob_end_clean();
        }

        // return content
        self::$contentHtml = $contentHtml;
        self::$contentJs = $contentJs;
        self::$contentCss = $contentCss;
        
        if(isset($value[0]) && $value[0] === true) echo '<style>'.$contentCss.'</style>'.$contentHtml.'<script>'.$contentJs.'</script>';

    }
}

//
// Directories
//
class dir
{
    protected static $register = [];

    public static function set($name, $value)
    {
        self::$register[$name] = $value;
    }

    public static function get($name = false)
    {
        if (empty($name)) {
            return self::$register;
        } else {
            if (isset(self::$register[$name])) {
                return self::$register[$name];
            } else {
                return false;
            }
        }
    }
}

//
// ENGINE
//
(new class() {
    public static $dir = [];
    public static $uri = [];
    public static $view = null;
    public static $apps = null;
    public static $page = [];

    public function __construct()
    {
        $memoryStart = memory_get_usage();
        $timeStart = microtime(1);

        dir::set('core', __DIR__.'/../../');
        dir::set('apps', dir::get('core').'../apps/');
        dir::set('oops', dir::get('core').'../oops/');
        dir::set('road', dir::get('core').'../road/');
        dir::set('view', dir::get('core').'../view/');
        dir::set('css', dir::get('core').'../http/css/');
        dir::set('js', dir::get('core').'../http/js/');
        dir::set('img', dir::get('core').'../http/img/');
        dir::set('font', dir::get('core').'../http/font/');
        dir::set('upload', dir::get('core').'../http/upload/');

        //
        // URI
        //
        self::$uri = (new uri())->uri;

        //
        // APPS
        //
        self::$apps = new apps();

        //
        // APPS
        //
        self::$view = new view();

        //
        // PAGE
        //
        self::$page['title'] = [];

        //
        // LOAD IMAGE
        //
        if (isset(self::$uri[0]) && preg_match('/\.png$/Uui', self::$uri[0])) {
            // uniq
            $uniq = substr(self::$uri[0], 0, 17);
            // width
            preg_match('/w\d+?/Uui', self::$uri[0], $width);
            if (isset($width[0]) && !empty($width[0])) {
                $width = (int) substr($width[0], 1);
            } else {
                $width = null;
            }
            // height
            preg_match('/h\d+?/Uui', self::$uri[0], $height);
            if (isset($height[0]) && !empty($height[0])) {
                $height = (int) substr($height[0], 1);
            } else {
                $height = null;
            }
            // resize
            $img = self::$apps->image->resize($uniq, $width, $height);
            if ($img === false) {
                header('HTTP/1.0 404 Not Found');
                exit;
            } else {
                // result
                header('Content-Type: image/png');
                die(file_get_contents($img));
            }
        }

        //
        // APPS EXEC
        //
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {

          // get name apps
            $name = $_SERVER['REQUEST_URI'];
            $name = explode('/', $name);
            $name = array_filter($name, function ($val, $key) {
                $val = (string) trim($val);
                if (!empty($val)) {
                    return $val;
                }
            }, ARRAY_FILTER_USE_BOTH);
            $name = implode('/', $name);

            // get value apps
            $json = json_decode(RAW_POST, true);
            if (!is_array($json)) {
                die('{}');
            }

            // exec apps
            $response = (new apps())($name, $json, 'http');

            // return
            if ($response === false) {
                @header('HTTP/1.0 404 Not Found', false, 404);
                exit;
            } else {
                @header('Content-Type: application/json');
                die(json_encode($response, JSON_UNESCAPED_UNICODE));
            }
        }

        //
        // APPS EXEC FROM GET URL
        //
        if (isset($_GET['apps'])) {
            $apps = $_GET['apps'];
            unset($_GET['apps']);
            $response = (new apps())($apps, $_GET, 'http');
            if ($response === false) {
                header('HTTP/1.0 404 Not Found', false, 404);
                exit;
            } else {
                header('Content-Type: application/json');
                die(json_encode($response, JSON_UNESCAPED_UNICODE));
            }
        }

        //
        // APPS EXEC FROM CLI
        //
        if(isset($_SERVER['argv'])){
            if(!isset($_SERVER['argv'][1])) die('');
            $response = (new apps())($_SERVER['argv'][1],null);
            die(json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        //
        // VIEW EXEC FROM GET URL
        //
        if (isset($_GET['view'])) {
            call_user_func_array([new view(), $_GET['view']], []);
            die('<style>'.view::$contentCss.'</style>'.view::$contentHtml.'<script>'.view::$contentJs.'</script>');
        }

        //
        // VIEW EXEC
        //
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/view') {
            $name = $_SERVER['REQUEST_URI'];
            $name = explode('/', $name);
            $name = array_filter($name, function ($val, $key) {
                $val = (string) trim($val);
                if (!empty($val)) {
                    return $val;
                }
            }, ARRAY_FILTER_USE_BOTH);
            $name = implode('/', $name);
            call_user_func_array([new view(), $name], []);
            die('<style>'.view::$contentCss.'</style>'.view::$contentHtml.'<script>'.view::$contentJs.'</script>');
        }

        //
        // PAGE
        //
        if (empty(self::$uri)) {
            $page = ['index'];
        } else {
            $page = self::$uri;
        }

        $isRoad = function ($road) {
            $file = dir::get('road').'/'.$road.'.json';
            if (is_file($file)) {
                return true;
            } else {
                return false;
            }
        };

        $openRoad = function ($road) {
            $file = dir::get('road').'/'.$road.'.json';
            if (!is_file($file)) {
                return false;
            }
            $file = file_get_contents($file);
            $open = json_decode($file, true);
            if (isset($open['view']) && is_array($open['view']) && !empty($open['view'])) {
                ksort($open['view']);
                reset($open['view']);
            }

            return $open;
        };

          // Is Auth
          $i = 1;
          while(true){

            $parent = array_slice($page,0,$i);
            if(empty($parent)) $parent = ['index'];
            $parent = implode('/',$parent);
            if(!$isRoad($parent)) break;
            $auth =  $openRoad($parent);
            if(isset($auth['auth'])){
              foreach($auth['auth'] as $val){
                $is = call_user_func_array([new apps(), $val], []);
                if($is !== true){
                  if(empty(self::$uri)){
                    die('<h1>STOP</h1>');
                  } else {
                      header('Location: /');
                  }
                }
              }
            };

            $i++;
            if(!isset($page[$i])) break;

          };

        $isLink = function() use (&$page,&$isLink,$isRoad,$openRoad) {

          // Current Road
            $link = implode('/', $page);
            $is = $isRoad($link);
            if ($is !== false) {
                return $openRoad($link);
            } else {
                $page = array_slice($page, 0, -1);
                if (empty($page)) {
                    return false;
                } else {
                    return $isLink();
                }
            }
        };

        $page = $isLink();

        // is view
        if ($page === false || !isset($page['view']) || empty($page['view'])) {
            header('HTTP/1.0 404 Not Found');
            die('<h1 style="font-size:5000%;margin:0px;padding:0px;text-align:center;line-height:63%;color:#eee;padding-top:8%;">404</h1>');
        }

        // title
        if (isset($page['title'])) {
            page::title($page['title']);
        }
        if (isset($page['description'])) {
            page::description($page['description']);
        }
        if (isset($page['keywords'])) {
            page::keywords($page['keywords']);
        }

        // view
        $view = $page['view'];
        if (!is_array($view)) {
            $view = [$view];
        }
        $split = [];
        foreach ($view as $key=>$val) {
            if (!empty($val)) {
                $split[count(explode('/', $val))][] = $val;
            }
        }
        krsort($split);

        // types
        if (!isset($page['type'])) {
            $type = 'page';
        } else {
            $type = $page['type'];
        }

        // create page
        while ($spot = array_pop($split)) {
            foreach ($spot as $key=>$val) {
                call_user_func_array([new view(), $val], []);
            }
        }

        // build html
        if (self::$uri !== false) {

          if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/page') {
            header('Content-Type: application/json');
            $response = [
                'css' => view::$contentCss,
                'js' => view::$contentJs,
                'html' => view::$contentHtml
            ];
            $response = json_encode($response,JSON_UNESCAPED_UNICODE);
            die($response);
          }
            switch ($type) {
              case 'view':
                die('<style>'.view::$contentCss.'</style>'.view::$contentHtml.'<script>'.view::$contentJs.'</script>');
              break;
              case 'page':
                (new page('<style>'.view::$contentCss.'</style>'.view::$contentHtml.'<script>'.view::$contentJs.'</script>'));
              break;
            }
        }
    }
});
