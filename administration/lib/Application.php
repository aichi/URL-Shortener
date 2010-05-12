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
			if (substr($method,0,3) == 'web') {
				$output[] = strtolower(substr($method, 3));
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
	 * User loggin method
	 */
	public function webLogin() {
		if ($this->loginManager->login($_POST['username'], $_POST['password'])) {
			header("HTTP/1.0 200 OK");
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	/**
	 * Render URL list
	 */
	public function webUrlList() {
		if ($this->isUserLogged()) {
			$result = $this->pm->getUrlList();
			echo json_encode($result);
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	/**
	 * check if HASH is unique
	 */
	public function webCheckHash() {
		if ($this->isUserLogged()) {
			$result = $this->pm->checkUniqueHash($_REQUEST['hash']);
			$r = new stdClass();
			$r->unique = $result;
			echo json_encode($r);
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	public function webNewUrl() {
		if ($this->isUserLogged()) {
			$r = new stdClass();
			$r->status = 'ok';
			$errors = array(); 
			
			
			$url = $_POST['url'];
			$hash = $_POST['hash'];
			
			if (empty($url)) {
				$r->status = 'error';
				$errors[] = 'emptyurl'; 
			}
			
			$regexp = "/^(ftp|ftps|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/";
			if (!preg_match($regexp, $url)) {
				$r->status = 'error';
				$errors[] = 'invalidurl';
			}
			
			if (!empty($hash) && !$this->pm->checkUniqueHash($hash)) {
				$r->status = 'error';
				$errors[] = 'nonuniquehash';
			}
			
            $hashregexp = "/[a-zA-z0-9_\-]*/";
            if (!empty($hash) && !preg_match($hashregexp, $hash)) {
                $r->status = 'error';
                $errors[] = 'invalidhash';
            }

			if ($r->status == 'error') {
				$r->errors = $errors;
			} else {
				$conf = $this->config['bitly'];
		
				$u = urlencode($_REQUEST['url']); 
				$urlli = "http://api.bit.ly/v3/shorten?login=".$conf['login']."&apiKey=".$conf['apikey']."&uri=$u&format=json";
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_URL, $urlli);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($ch); 
				curl_close($ch);

				$data = json_decode($response);
                
				if ($data && $data->status_code == 200) {
					$bitly = $data->data->hash;
					//ukladam novou url
					if ($data->data->new_hash == 1) {
						$result = $this->pm->saveUrl($url, $bitly, $hash);
						if ($result == false) {
							$r->status = 'error';
							$r->errorText = 'Error when trying save data. Please try again.';
						} else {
							$r->data = $result;
							$r->data['shorten_url'] = $this->config['shortenUrl'] . $result['hash'];
						}
					//ziskam pro starou url data
					} else {
						$result = $this->pm->getUrlByBitly($bitly);
						if ($result == false) {
							$r->status = 'error';
							$r->errorText = 'Error when trying load data. Please try again.';
						} else {
							$r->data = $result;
							$r->data['shorten_url'] = $this->config['shortenUrl'] . $result['hash'];
						}
					}
				} else {
					$r->status = 'error';
					$r->errorText = $data->status_txt;
				}
			}
						
			echo json_encode($r);
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
		
}
?>