<?php
namespace UrlShortener;

class Application {
	protected $config = array();
	protected $server = 'development';
	protected static $instance = null;
	protected $basePath = '/';

	/**
	 * Persistence Manager Instance
	 * @var IPersistence
	 */
	protected $pm = null;
	/**
	 * Login Manager Instance
	 * @var ILogin
	 */
	protected $loginManager = null;
	/**
	 * Shortener Connector Instance
	 * @var IShortenerConnector
	 */
	protected $shortener = null;

	/**
	 * Default name of method which will be executed if JS part not specify
	 * other
	 * @var string
	 */
	protected $defaultMethodName = "webUrlList";

	private $userInput = array();


	public function __construct($conf) {
		$this->server = (strpos($_SERVER["HTTP_HOST"], 'localhost') === false) ? 'production':'development' ;
		$this->config = $conf[$this->server];

		$this->init();
	}


	/**
	 * initialisation
	 *
	 */
	protected function init() {
		$p = $this->config['persistentManager'];
		$pm = new $p();
		if ($pm instanceof IPersistence) {
			$this->pm = $pm;
		} else {
			throw new \ErrorException("PersitentManager have to implement UrlShortener\\IPersistence interface.");
		}
		if (!$pm->init($this->config["connection"])) {
			header("HTTP/1.0 503 Service Unavailable");
			throw new \ErrorException("Something went wrong.");
		}

		$l = $this->config['loginManager'];
		$loginManager = new $l($this->config);
		if ($loginManager instanceof ILogin) {
			$this->loginManager = $loginManager;
		} else {
			throw new \ErrorException("LoginManager have to implement UrlShortener\\ILogin interface.");
		}

		$s = $this->config['shortenerConnector'];
		$shortenerConnector = new $s($this->config["shortenerConnectorConfig"]);
		if ($shortenerConnector instanceof IShortenerConnector) {
			$this->shortener = $shortenerConnector;
		} else {
			throw new \ErrorException("ShortenerConnector have to implement UrlShortener\\IShortenerConnector interface.");
		}
		
		//if ($server == 'development') {
			$this->basePath = realpath(dirname(__FILE__).PATH_SEPARATOR.'..'.PATH_SEPARATOR);
		//} else {
		//	$this->basePath = '/home/testproject/www/';
    	//}

		$this->userInputSanitization();
	}

	/**
	 * This method sanitize all user input which is needed for Application methods.
	 * All methods are accessing Application->userInput array with filtered variables.
	 */
	protected function userInputSanitization() {
		$this->userInput["method"] = $this->validateMethod(
			filter_input(INPUT_GET, 'method', FILTER_SANITIZE_STRING,
				array("options" => array("flags" => array(FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH)))));
		$this->userInput["page"] = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT);
		//workaround, because INPUT_GET | INPUT_POST not work
//		$this->userInput["url"] = $this->validateUrl(filter_input(INPUT_GET | INPUT_POST, 'url', FILTER_SANITIZE_URL));
//		$this->userInput["hash"] = $this->validateHash(filter_input(INPUT_GET | INPUT_POST, 'hash', FILTER_SANITIZE_STRING));
		$this->userInput["url"] = isset($_REQUEST['url']) ? $this->validateUrl($_REQUEST['url']) : false;
		$this->userInput["hash"] = isset($_REQUEST['hash']) ? $this->validateHash($_REQUEST['hash']) : false;
		$this->userInput["username"] = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
		$this->userInput["password"] = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
	}

	/**
	 * Validate if method sub name is
	 * @param String $method
	 * @return String|false mixed
	 */
	public function validateMethod($method) {
		$method = filter_var($method, FILTER_VALIDATE_REGEXP,
			array("options"=>array("regexp"=>"/^[a-zA-z0-9_]*$/")));
		if ($method) {
			$method = "web".$method;

			if (!in_array(strtolower($method), $this->allowedPages())) {
				$method = false;
			}
		}
        return $method;
    }

	public function validateHash($hash) {
		$hash = filter_var($hash, FILTER_VALIDATE_REGEXP,
			array("options"=>array("regexp"=>"/^[a-zA-z0-9_\-\.]*$/")));
        return $hash;
    }

	public function validateUrl($url) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}

	/**
	 * Accessing $_REQUEST["method"] property to determine which function JS
	 * part wanted to run.
	 */
	public function execute() {
		$method = $this->userInput["method"];

		if ($this->isUserLogged()) {
			$this->{$method ? $method : $this->defaultMethodName}();
		} else {
			$this->webLogin();
		}
	}
	
	/**
	 * Returns array of methods callable via JS. It is taking
	 * all methods starting with 'web' like 'webLogin'.
	 * @return array
	 */
	public function allowedPages() {
		$methods = get_class_methods($this);
		$output = array();
		foreach ($methods as $method) {
			if (substr($method,0,3) == 'web') {
				$output[] = strtolower($method);
			}
		}
		
		return $output;
	}
	
	/**
	 * Asks loginManager if user is logged.
	 */
	public function isUserLogged() {
		return $this->loginManager->isLogged();
	}
	
	/**
	 * User loggin method callable wia AJAX
	 */
	public function webLogin() {
		if (isset($this->userInput["username"]) && isset($this->userInput["password"]) &&
				$this->loginManager->login($this->userInput['username'], $this->userInput['password'])) {
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
			$result = array(
				"url" => $this->config['shortenUrl'],
				"list" => $this->pm->getUrlList()
			);

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
			$result = $this->pm->checkUniqueHash($this->userInput['hash']);
			$r = new \stdClass();
			$r->unique = $result;
			echo json_encode($r);
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	public function webNewUrl() {
		if ($this->isUserLogged()) {
			$r = new \stdClass();
			$r->status = 'ok';
			$errors = array(); 
			
			
			$url = $this->userInput['url'];
			$hash = $this->userInput['hash'];
			
			if (!$url) {
				$r->status = 'error';
				$errors[] = 'invalidurl';
			}

			if (!$hash) {
                $r->status = 'error';
                $errors[] = 'invalidhash';
            }

			if (!$this->pm->checkUniqueHash($hash)) {
				$r->status = 'error';
				$errors[] = 'nonuniquehash';
			}
			


			if ($r->status == 'error') {
				$r->errors = $errors;
			} else {
				$data = $this->shortener->newUrl($this->userInput['url']);

				if ($data && $data['status'] == 200) {
					$shortenerHash = $data['hash'];
					//ukladam novou url
					if ($data['new'] == 1) {
						$result = $this->pm->saveUrl($url, $shortenerHash, $hash);
						if ($result == false) {
							$r->status = 'error';
							$r->errorText = 'Error when trying save data. Please try again.';
						} else {
							$r->data = $result;
							$r->data['shorten_url'] = $this->config['shortenUrl'] . $result['hash'];
						}
					//ziskam pro starou url data
					} else {
						$result = $this->pm->getUrlByShortenerHash($shortenerHash);
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
					$r->errorText = $data['errorText'];
				}
			}
						
			echo json_encode($r);
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}

	/**
	 * returns information about url
	 */
	public function webUrlDetail() {
		if ($this->isUserLogged()) {
			$r = new \stdClass();
			if ($hash = $this->validateHash($this->userInput['hash'])) {
                $result = $this->pm->getUrlByHash($hash);
				if ($result == false) {
					$r->status = 'error';
					$r->errorText = 'Error when trying load data. Please try again.';
				} else {
					$r->data =  new \stdClass();
					$r->data->hash = $result['hash'];
					$r->data->shorten_url = $this->config['shortenUrl'] . $result['hash'];
					$r->data->url = $result["url"];
					$r->data->id = $result["shortenerHash"];

					$data = $this->shortener->statisticForUrl($result['shortenerHash']);

                    if ($data && $data['status'] == 200) {
						$r->data->statistics =  new \stdClass();
                        $r->data->statistics->clicks = $data['userClicks'];
                        $r->data->statistics->global_clicks = $data['globalClicks'];
                        $r->data->statistics->statistics_url = $data['statUrl'];
                    }
				}
            } else {
                $r->status = 'error';
                $r->errorText = 'Hash is not valid.';
            }
			echo json_encode($r);
		} else {
			header("HTTP/1.0 401 Unauthorized");
        }
	}

    /**
     * logout logged user
     */
	public function webLogout() {
    	if ($this->isUserLogged()) {
			if($this->loginManager->logout()) {
                header("HTTP/1.0 205 Reset Content");
            } else {
                header("HTTP/1.0 403 Forbidden");
            }
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
    }

    /**
     * delete given url
     */
    public function webDeleteLink() {
        if ($this->isUserLogged()) {
            $r = new \stdClass();
            if ($hash = $this->validateHash($this->userInput['hash'])) {
                $result = $this->pm->deleteLink($hash);

                $r->status = $result ? 'ok' : 'error';

            } else {
                $r->status = 'error';
                $r->errorText = 'Hash is not valid.';
            }
            echo json_encode($r);
        } else {
			header("HTTP/1.0 401 Unauthorized");
        }
    }
}
?>