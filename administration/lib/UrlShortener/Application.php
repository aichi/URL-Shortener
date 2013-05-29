<?php
namespace UrlShortener;
/**
 * Application is a main PHP class of URL shortener application. It exposes public methods which are callable
 * by JavaScript part. These methods start on string 'web'. Application also reads configuration file and instantiates
 * classes fulfilling ILogin, IPersistence and IShortenerConnector interfaces defined in configuration file.
 * @package UrlShortener
 */
class Application {
	/**
	 * Copy of configuration file.
	 * @var array
	 */
	protected $config = array();

	/**
	 * Application mode is 'development' or 'production'. On this variable depends which part of configuration file
	 * is used.
	 * @var string
	 */
	protected $server = 'development';

	/**
	 * base path where URL shortener project is stored (where server.php file is)
	 * @var string
	 */
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
	 * Default name of method which will be executed if JavaScript part don't specify any other.
	 * @var string
	 */
	protected $defaultMethodName = "webUrlList";

	/**
	 * Array of user input collected from GET or POST. This array contains already sanitized known properties.
	 * @see Application::userInputSanitization
	 * @var array
	 */
	private $userInput = array();

	/**
	 * Constructor initializes URL shortener application by given configuration.
	 * @param array $conf
	 */
	public function __construct($conf) {
		$this->server = (strpos($_SERVER["HTTP_HOST"], 'localhost') === false) ? 'production':'development' ;
		$this->config = $conf[$this->server];

		$this->init();
	}

	/**
	 * Initialisation method is called from constructor
	 * @throws \ErrorException
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
	 * This method sanitizes all user input which is needed for Application methods.
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
	 * Method validates name given by JavaScript part of method which should be called.
	 * @param string $method Method name without 'web' prefix
	 * @return string|boolean Full name of method (starting with 'web') or false if error.
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

	/**
	 * Filter out all non valid characters from given hash if they differ from letters, numbers and _-.
	 * @param string $hash
	 * @return string
	 */
	public function validateHash($hash) {
		$hash = filter_var($hash, FILTER_VALIDATE_REGEXP,
			array("options"=>array("regexp"=>"/^[a-zA-z0-9_\-\.]*$/")));
        return $hash;
    }

	/**
	 * Validates URL
	 * @param string $url
	 * @return string
	 */
	public function validateUrl($url) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}

	/**
	 * Access $_REQUEST["method"] property to determine which function JavaScript part wants to run and runs it.
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
	 * Returns array of methods callable via JavaScript. It takes all methods starting with 'web' in that class.
	 * Like 'webLogin'.
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
	 * Asks ILogin implementer if user is logged.
	 */
	public function isUserLogged() {
		return $this->loginManager->isLogged();
	}
	
	/**
	 * User login method callable from JavaScript. This method must be called with 'username' and 'password' parameters
	 * in HTTP request.
	 * Response is HTTP 200 if user is successfully logged in or HTTP 401 if no user is logged.
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
	 * Returns URL list.
	 * Response is JSON object with two keys
	 * 		'url': base URL of application
	 * 		'list: associative array with keys:
	 * 				'url': full URL
	 * 				'hash': local hash for URL
	 * 				'shortenerHash': hash from remote shortener provider
	 * or HTTP 401 if no user is logged.
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
	 * Checks if hash is unique. This method must be called with 'hash' parameter in HTTP request.
	 * Response is JSON object with key 'unique' and boolean value or HTTP 401 if no user is logged.
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

	/**
	 * Saves new URL into storage. This method must be called with 'url' and optional 'hash' parameters in HTTP request.
	 * Response is JSON object with keys:
	 * 		'status': values 'ok' or 'error' indicates result of URL storing and shortening.
	 *		--- 'ok' ---
	 *		'data': associative array with keys 'url' and 'hash'
	 *		'shorten_url': represents shorten URL (Application URL + hash)
	 *		--- 'error' ---
	 *		'errors': array of strings indicating errors: 'invalidurl', 'invalidhash' and 'nonuniquehash'
	 *		'errorText': error text from external shortener service
	 * or HTTP 401 if no user is logged.
	 */
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
					// Save new URL:
					if ($data['new'] == 1) {
						$this->_saveNewUrl($url, $shortenerHash, $hash, $r);
					// There is old URL flag from Bit.ly:
					} else {
						// Is old URL in our database?
						$result = $this->pm->getUrlByShortenerHash($shortenerHash);
						if ($result == false) {
							$this->_saveNewUrl($url, $shortenerHash, $hash, $r);
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
	 * Helper method called from webNewUrl method.
	 * @param string $url
	 * @param string $shortenerHash
	 * @param string $hash
	 * @param /stdClass $r This response property is modified in method
	 */
	private function _saveNewUrl($url, $shortenerHash, $hash, $r)
	{
		$result = $this->pm->saveUrl($url, $shortenerHash, $hash);
		if ($result == false) {
			$r->status = 'error';
			$r->errorText = 'Error when trying save data. Please try again.';
		} else {
			$r->data = $result;
			$r->data['shorten_url'] = $this->config['shortenUrl'] . $result['hash'];
		}
	}

	/**
	 * Returns statistical information about URL. This method must be called with 'hash' parameter in HTTP request.
	 * Response is JSON object with keys:
	 *		'data': associative array with keys
	 * 			'hash': Local hash
	 * 			'shorten_url': represents shorten URL (Application URL + hash)
	 * 			'url': full unshorten URL
	 * 			'id': hash from external shortener
	 * 			optionally:
	 * 			'statistics': associative array with keys:
	 * 				'clicks': amount of clicks from this application
	 * 				'global_clicks': amount of clicks globally in external shorterner through their hash
	 * 				'statistics_url': URL to external shortener statistics page
	 *		or these when error occurs:
	 * 		'status': 'error'
	 *		'errorText': error text from external shortener service
	 * or HTTP 401 if no user is logged.
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
     * Logs out logged user.
	 * Response is HTTP 205 if user is successfully logged out or HTTP 401 if no user is logged or 403 if logout process
	 * failed.
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
     * Deletes given URL. This method must be called with 'hash' parameter in HTTP request.
	 * Response is JSON object with keys:
	 * 		'status': values 'ok' or 'error' indicates result of URL storing and shortening
	 *		'errorText': error text
	 * or HTTP 401 if no user is logged.
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