/**
 * @class Definition of main namespace
 * @name UrlShortener
 */
window.UrlShortener = self.UrlShortener || {
	/**
	 * show element
	 * @param elm
	 */
	show: function(elm) {
		elm.style.display = "";
	},

	/**
	 * hide element
	 * @param elm
	 */
	hide: function(elm) {
		elm.style.display = "none";
	},

	/**
	 * Check if given HTML element has CSS class.
	 * @param {HTMLElement} element Tested element
	 * @param {String} className Tested CSS class
	 * @return {Boolean} true|false
	 */
	hasClass: function(element, className) {
		var names = element.className.split(" "),
			length = names.length,
			i;
		for (i = 0; i < length; i++) {
			if (names[i].toLowerCase() == className.toLowerCase()) { return true; }
		}
		return false;
	},

	/**
	 * Add to given HTML element a CSS class.
	 * @param {HTMLElement} element Given HTML element
	 * @param {String} className New CSS class
	 */
	addClass: function(element, className) {
		if (window.UrlShortener.hasClass(element, className)) { return; }
		element.className += " " + className;
	},

	/**
	 * Remove CSS class from given element. Do nothing when there is no class at the element.
	 * @param {HTMLElement} element Given HTML elememnt
	 * @param {String} className CSS class
	 */
	removeClass: function(element,className) {
		var names = element.className.split(" "),
			length = names.length,
			newClassArr = [],
			i;
		for (i = 0; i < length; i++) {
			if (names[i].toLowerCase() != className.toLowerCase()) { newClassArr.push(names[i]); }
		}
		element.className = newClassArr.join(" ");
	},

	/**
	 * Method to set cookie
	 * @param {String} c_name Cookie name
	 * @param {String} value Value
	 * @param {Number} exdays Expired in # of days
	 */
	setCookie: function(c_name, value, exdays) {
		var exdate = new Date(),
			c_value;

		exdate.setDate(exdate.getDate() + exdays);
		c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
		document.cookie = c_name + "=" + c_value;
	},

	/**
	 * Get cookie value
	 * @param {String} c_name Cookie name
	 * @returns {String|undefined}
	 */
	getCookie: function(c_name) {
		var i,
			x,
			y,
			cookies=document.cookie.split(";");

		for (i = 0; i < cookies.length; i++) {
			x = cookies[i].substr(0 ,cookies[i].indexOf("="));
			y = cookies[i].substr(cookies[i].indexOf("=") + 1);
			x = x.replace(/^\s+|\s+$/g, "");
			if (x == c_name) {
				return unescape(y);
			}
		}
	},

	/**
	 * counter used as id for event stored in addListener method to _eventHolder
	 */
	_eventId : 0,

	/**
	 * Folder with all attached events which should be removed only by id
	 */
	_eventHolder: {},

	/**
	 * Method which attach event listener to HTML element, it returns a id
	 * of the event for easier removing
	 * @param {HTMLElement} elm
	 * @param {String} type
	 * @param {Function} action
	 * @param {Boolean} capture
	 * @returns {String}
	 */
	addListener: function(elm, type, action, capture) {
		var key = "e" + (window.UrlShortener._eventId++);
		elm.addEventListener(type, action, capture);
		window.UrlShortener._eventHolder[key] =[elm, type, action, capture];
		return key;
	},

	/**
	 * Removes event listener by id
	 * @param {String} key
	 */
	removeListener: function(key) {
		var data = window.UrlShortener._eventHolder[key];
		data[0].removeEventListener(data[1], data[2], data[3]);
		data = null;
	},

	extend: function(obj, ext) {
		for (var k in ext) {
			obj[k] = ext[k];
		}
	}
};

(function(Shortener){
	var UNDEF,
		gel = document.getElementById.bind(document);

	/**
	 * @class Application is taking care about registering all necessary modules and handling history,
	 * menu and login information.
	 * @name UrlShortener.Application
	 */
	Shortener.Application = Proto.extend(/**@lends UrlShortener.Application# */{
		/**
		 * Constructor
		 * @constructor
		 * @public
		 */
		constructor: function() {
			var that = this,
				History = that._history = window.History,
				module,
				params = {},
				i, a,
				url,
				historyCallback = function() { // Note: We are using statechange instead of popstate
					var state = History.getState(); // Note: We are using History.getState() instead of event.state
					//History.log(state.data, state.title, state.url);
					//contract between application and modules is that they store module name and params into state object
					//under 'module' and 'params' keys.
					//console.log("activate module from history", state.data.module)
					that._attachModule(state.data.module, state.data.params);
				};

			 // Bind to StateChange Event
			History.Adapter.bind(window, "statechange", historyCallback);

			// Parse initial URL, contract between application and modules is that url looks like
			// ?moduleName or if there are params than
			// ?moduleName&params=params_url_encoded_JSON_string
			if (url = window.location.search) {
				url = url.substr(1).split("&");
				module = url[0];
				if (url[1]) {
					url = url[1].split("=");
					if (url[0] == "params") {
						params = JSON.parse(decodeURIComponent(url[1]));
					}
				}
			}

			that.init();
			that.activateModule(module, params);
			that._attachModule(module, params);
		},

		/**
		 * Events id cache
		 * @type String[]
		 */
		_ec: [],

		/**
		 * DOM elements cache
		 * @type Object
		 */
		_dom: {},

		/**
		 * modules are rendering and taking care about main part of page.
		 * application is taking care about menu, and giving modules some
		 * space to render in. Special module is Login which takes care also
		 * about session handling.
		 * @type Object
		 */
		modules: {},

		/**
		 * module which is active and rendered
		 * @type UrlShortener.IModule
		 */
		activeModule: null,

		/**
		 * Active menu button LI DOM node
		 * @type HTMLElement
		 */
		_activeMenuNode: null,

		/**
		 * Registry of menu items with their names and callback functions
		 * {name: "Name", callback: function(){}, index: 0, node: HTMLElement}
		 *
		 * - index is determining order
		 * - node is prepared node element, because it is not needed to recreate them all the time user add or remove
		 * item from menu
		 * @type Object[]
		 */
		_menuRegistry: [],

		/**
		 * destructor called on window.unload
		 */
		destructor: function() {
			var that = this,
				dom = that._dom,
				p;

			that._ec.forEach(Shortener.removeListener, Shortener);

			that.showedBox = null;
			that.urlListElmClick = null;

			//TODO: destroy modules, logout

			for (p in dom) {
				dom[p] = null;
			}
		},

		/**
		 * init start listenning on all necessary elements
		 */
		init: function() {
			var that = this,
				ec = that._ec,
				dom = that._dom,
				hide = Shortener.hide,
				module;

			ec.push(Shortener.addListener(window, "unload", that.destructor.bind(that)));

			//throbber
			dom.throbberBox = gel("throbber-box");
			hide(dom.throbberBox);

			//menu
			dom.menuBox = gel("menu-box");
			dom.menuBoxList = gel("menu-box-list");

			hide(dom.menuBox);

			//content
			dom.contentNode = gel("content-box");

			//login module
			that._onLogin = that._onLogin.bind(that);
			that._onLogout = that._onLogout.bind(that);
			module = Shortener.Login.new(that);
			that.modules[module.name] = module;
			module.addListener("login", that._onLogin);
			module.addListener("logout", that._onLogout);

			//url-list module
			module = Shortener.UrlList.new(that);
			that.modules[module.name] = module;
			//new-url module
			module = Shortener.NewUrl.new(that);
			that.modules[module.name] = module;
			//url-detail module
			module = Shortener.UrlDetail.new(that);
			that.modules[module.name] = module;

			//Error page
			dom.errorPage = gel("error-page");
			dom.errorCode = gel("error-code");

			dom.errorPage.parentNode.removeChild(dom.errorPage);
		},

		/**
		 * Publicly callable method to show module by given module name or given module instance.
		 * Calling without moduleName parameter cause loading default module which is URL list.
		 *
		 * In general this method check module name or given module and call on module method Module.registerHistory().
		 * Module internally can prepare some data to store to history and call Application.pushToHistory(). Than on
		 * history change event is called callback method Application._attachModule() which check module again and than
		 * detach old module and attach new module.
		 *
		 * TL:DR Activating module is asynchronous and is always prepared by calling this method and invoked by history
		 * change event.
		 *
		 * @param {String|UrlShortener.Module} [moduleName]
		 * @param {Object} [params]
		 */
		activateModule: function(moduleName, params) {
			var that = this,
				module = that._prepareModule(moduleName);

			module.registerHistory(params);
		},

		/**
		 * Called when URL history is changed as a callback which currently change modules.
		 * @param {String|UrlShortener.Module} [moduleName]
		 * @param {Object} [params]
		 */
		_attachModule: function(moduleName, params) {
			var that = this,
				modules = that.modules,
				login = modules.login,
				module = that._prepareModule(moduleName);

			if (module == login) {
				params = {};
				//hide Menu
				Shortener.hide(that._dom.menuBox);
			} else {
				//show Menu
				Shortener.show(that._dom.menuBox);
			}

			that.activeModule && that.activeModule.detach(that._dom.contentNode);
			(that.activeModule = module).attach(that._dom.contentNode, params);
		},

		/**
		 * Method gets module or module name and check if it is valid module, if user is logged and return correct module.
		 * @param {String|UrlShortener.Module} [moduleName]
		 * @returns {UrlShortener.Module}
		 */
		_prepareModule: function(moduleName) {
			var that = this,
				modules = that.modules,
				login = modules.login,
				module;

			if (!login.isLogged()) {
				moduleName = "login";
			}

			module = moduleName && typeof moduleName == "string" ? modules[moduleName] : moduleName;

			if (!module) module = that.getDefaultModule();

			return module;
		},

		/**
		 * Method returns default module which is URL list
		 * @returns {UrlShortener.Module}
		 */
		getDefaultModule: function() {
			//TODO make it in nicer way - module or app should define default module
			return this.modules.urlList;
		},

		/**
		 * Method provides all components and unified way to request data
		 * This method also wrap callback to test status code of response.
		 * @param {String} serverMethod
		 * @param {Function} callback
		 * @param {String} method
		 * @param {Object} data
		 */
		requestUrl: function(serverMethod, callback, method, data) {
			var that = this,
				Request = window.XMLHttpRequest,
				rq = new Request(),
				url = "server.php?method=" + serverMethod;

			//sanity GET method
			method = method ? method : "get";

			//serialize data
			if (data && typeof data == "object") {
				data = that._serializePostData(data);
			}

			rq.open(method, url, true);

			//if not GET method set header for data
			if (method.toLowerCase() !== "get") {
				rq.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			}

			rq.onreadystatechange = function() {
				if (rq.readyState != 4) { return; }

				var txt = rq.responseText,
					status = rq.status;

				if (status >= 400 && status !== 401) { // There is error
					that._showErrorPage(status);
				} else { //OK, or unauthorized
					if (status == 401) { //401 unauthorised -> login
						that.logout();
					}
					callback(txt, status);
				}
			};

			rq.send(data);
		},
		
		/**
		 * Serialize data for XMLHttpRequest POST method
		 * @param {Object} data
		 * @return {String}
		 */
		_serializePostData: function(data) {
			if (typeof(data) == "string") return data;
			if (!data) return null;
			
			var postData = [],
				p,
				value,
				i;
			
			for (p in data) {
				value = data[p];
				if (!(value instanceof Array)) value = [value];
				for (i = 0; i < value.length; i++) {
					postData.push(encodeURIComponent(p) + "=" + encodeURIComponent(value[i]));
				}
			}
			return postData.join("&");
		},

		/**
		 * Helper method to show page with error for HTTP error codes >400
		 * @param {Number} code
		 */
		_showErrorPage: function(code) {
			var that = this,
				dom = that._dom;

			that.logout();
			that.activeModule && that.activeModule.detach(dom.contentNode);
			Shortener.hide(dom.menuBox);

//			if (status >= 400 && status < 500) {
//				//404 page
//				console.log(status)
//			} else if (status >= 500) {
//				//500 page
//				console.log(status)
//			}
			dom.errorCode.innerHTML = code;
			dom.contentNode.appendChild(dom.errorPage);
		},

		/**
		 * called when user is logged in as a signal 'login' callback
		 */
		_onLogin: function(){
			var that = this,
				dom = that._dom,
				contentNode = dom.contentNode,
				modules = that.modules;

			//show Menu
			Shortener.show(dom.menuBox);

			//show default module
			that.activateModule();
		},

		/**
		 * call from everywhere
		 */
		logout : function() {
			var that = this;

			//that.activeModule.detach(that._dom.contentNode);
			that._onLogout();
		},

		/**
		 * called from logout method and also on signal from Login class
		 */
		_onLogout: function() {
			var that = this,
				dom = that._dom;
			//hide Menu
			Shortener.hide(dom.menuBox);

			//show Login view
			that.activateModule("login");
		},

		/**
		 * Visualize active menu item by adding CSS class to it
		 * @param {Function} [callback] To which node shall we add 'active' class
		 */
		menuActivateItem: function(callback) {
			var that = this,
				menuRegistry = that._menuRegistry,
				i = menuRegistry.length,
				node,
				activeMenuNode = this._activeMenuNode;
			//debugger
			activeMenuNode && Shortener.removeClass(activeMenuNode, "active");
			if (callback) {
				while (i--) {
					if (menuRegistry[i].callback == callback && (node = menuRegistry[i].node)) {
						Shortener.addClass(node, "active");
						break;
					}
				}
			}
			this._activeMenuNode = node;
		},

		/**
		 * Method registers text and callback on menu item.
		 * @param {String} text Menu text
		 * @param {Function} callback Callback function
		 * @param {Number} [index] Optional index to determine order of items in menu
		 */
		menuRegister: function(text, callback, index) {
			var that = this,
				menuRegistry = that._menuRegistry,
				length = menuRegistry.length;

			if (!text || !callback) return;

			index = index == +index ? index : length;

			menuRegistry[length] = {text: text, callback: callback, index: index};

			that._updateMenu();
		},

		/**
		 * Remove item from menu. This removal is happening for given callback method. Expected is that is no more than
		 * one menu item for one callback.
		 * @param {Function} callback
		 */
		menuUnregister: function(callback) {
			var that = this,
				menuRegistry = that._menuRegistry,
				i = menuRegistry.length;

			while (i--) {
				if (menuRegistry[i].callback == callback) {
					menuRegistry.splice(i, 1);
				}
			}

			that._updateMenu();
		},

		/**
		 * This method update items in menu = render them and attach click listener on them
		 */
		_updateMenu: function() {
			var that = this,
				menuRegistry = that._menuRegistry,
				menu = that._dom.menuBoxList,
				length = menuRegistry.length,
				i,
				n,
				a,
				callback;

			//sort by index
			menuRegistry.sort(function(a, b) {return a.index > b.index ? 1 : a.index < b.index ? -1 : 0});

			menu.innerHTML = "";

			for (i = 0; i < length; i++) {
				if (!menuRegistry[i].node) {
					n = document.createElement("li");
					a = document.createElement("a");
					a.innerHTML = menuRegistry[i].text;
					callback = menuRegistry[i].callback;
					a.addEventListener("click", function(evt){ // debugger;
						evt.preventDefault();

						callback(evt);
					}, false);
					n.appendChild(a);
					menuRegistry[i].node = n;
				}

				menu.appendChild(menuRegistry[i].node);
			}
		},
		/**
		 * Method allows to push new state to history, it is called by Modules when they are activated or
		 * when their state changed
		 * @param {Object} state
		 * @param {String} title
		 * @param {String} url
		 */
		pushToHistory: function(state, title, url) {
			this._history.pushState(state, title, url);
		}
	});

}(UrlShortener));
