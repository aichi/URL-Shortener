<?php
class Application extends TObjectStatic  {
	protected $config = array();
	protected $server = 'development';
	protected static $instance = null;
	protected $basePath = '/';
	
	protected $pm = null; //Persistence Manager Instance
	protected $loginManager = null; //Login Manager Instance
	
	protected function __construct($conf) {
		$this->server = (strpos($_SERVER["HTTP_HOST"], 'localhost') === false) ? 'production':'development' ;
		$this->config = $conf[$this->server];	
	}
	
	
	/**
	 * return one instance
	 *
	 * @return Application
	 */
	public static function getInstance($conf) {
		if (Application::$instance == null) {
			$app = new Application($conf);
			$app->init();
			Application::$instance = $app;
		}
		return Application::$instance;
	}
	
	/**
	 * initialisation
	 *
	 */
	protected function init() {
		$p = $this->config['persistentManager'];
		require_once "./lib/$p.php";
		$pm = call_user_func(array($p, 'getInstance'), $this->config["connection"]);
		if ($pm instanceof IPersistence) {
			$this->pm = $pm;
		} else {
			throw new ErrorException("PersitentManager have to implement IPersistence interface.");
		}
		
		$l = $this->config['loginManager'];
		require_once "./lib/$l.php";
		$loginManager = call_user_func(array($l,'getInstance'));
		if ($loginManager instanceof ILogin) {
			$this->loginManager = $loginManager;
		} else {
			throw new ErrorException("LoginManager have to implement ILogin interface.");
		}
		
		//if ($server == 'development') {
			$this->BasePath = realpath(dirname(__FILE__).PATH_SEPARATOR.'..'.PATH_SEPARATOR);
		//} else {
		//	$this->BasePath = '/home/testproject/www/';
    	//}
    	
	}
	
	/**
	 * funkce vraci pole stringu s validnimi hodnotami pro parametr page v URL
	 * tyto hodnoty se odviji od nazvu metod renderXX()
	 * @return array
	 */
	public function allowedPages() {
		$methods = get_class_methods($this);
		$output = array();
		foreach ($methods as $method) {
			if (substr($method,0,6) == 'render') {
				$output[] = strtolower(substr($method, 6));
			}
		}
		
		return $output;
	}
	
	/**
	 * dotaz na loginManager zda je uzivatel nalogovan
	 */
	public function isUserLogged() {
		return $this->loginManager->isLogged();
	}
	
	/**
	 * render main page
	 *
	 */
	public function renderIndex() {
		/*$ab = new AddressBook();
		if (isset($_REQUEST['page'])){
			$page = (int)$_REQUEST['page'];
			$page = $page < 1 ? 1 : $page;
		} else {
			$page = 1;
		}
		$ab->init($page,10);
		
				
		$this->smarty->assign('addressBook', $ab);
		$this->smarty->display('index.stpl');*/
		
		echo "<a href='?page=addlink'>Nová adresa</a><br />";
	}
	
	public function renderAddLink() {
		echo "<form method='post' action='?page=savelink'>";
		
		echo "URL: <input type='text' name='url' value='' />";
		echo "Náš volitelný hash: <input type='text' name='hash' value='' />";
		echo "<input type='submit' />";
		
		echo "</form>";
	}
	
	public function renderSaveLink() {
		$conf = $this->config['bitly'];
		
		$u = urlencode($_REQUEST['url']); 
		
		$url = "http://api.bit.ly/v3/shorten?login=".$conf['login']."&apiKey=".$conf['apikey']."&uri=$u&format=json";
		
		$response = file_get_contents($url);
		$data = json_decode($response);
		$this->pm->saveLink($_REQUEST['url'], $data->data->hash, $_REQUEST['hash'] ? $_REQUEST['hash'] : substr(md5(time()),0,6));
		
	}
	
}
?>