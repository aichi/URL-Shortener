(function(Shortener){
	var gel = document.getElementById.bind(document),
		setCookie = Shortener.setCookie,
		getCookie = Shortener.getCookie,
		Login =

	/**
	 * @class This module handles user login and logout. It also keeps cookie alive.
	 * @name UrlShortener.Login
	 * @implements UrlShortener.Module
	 */
	Shortener.Login = Shortener.Module.extend(/** @lends UrlShortener.Login# */{
		/**
		 * Constructor
		 * @constructor
		 * @public
		 * @param {UrlShortener.Application} app
		 */
		constructor: function(app) {
			Login.super.constructor.apply(this, arguments);

			var that = this;
			that.name = "login";

			that._loginRequestCallback = that._loginRequestCallback.bind(that);
			that.init();
		},

		/**
		 * Mandatory property defined in Module interface
		 * @public
		 * @type {String}
		 * @name UrlShortener.Login#name
		 */
		//defined in constructor()

		//defined in UrlShortener.Module
		/*
		_ec: [],
		_dom: {},
		_app: null,*/

		/**
		 * destructor called on window.unload
		 */
		destructor: function() {
			var that = this,
				dom = that._dom,
				p;

			that._ec.forEach(Shortener.removeListener, Shortener);
			for (p in dom) {
				dom[p] = null;
			}
		},

		/**
		 * init start listening on all elements
		 */
		init: function() {
			var that = this,
				ec = that._ec,
				dom = that._dom,
				app = that._app,
				hide = Shortener.hide;

			dom.node = gel("login-box");
			dom.loginForm = gel("login-form");
			dom.username = gel("username");
			dom.password = gel("password");
			dom.loginButton = gel("login-button");
			dom.usernameError = gel("username-error");
			dom.passwordError = gel("password-error");
			dom.loginError = gel("login-error");

			hide(dom.usernameError);
			hide(dom.passwordError);
			hide(dom.loginError);

			ec.push(Shortener.addListener(dom.loginButton, "click", that._loginClick.bind(that)));
			ec.push(Shortener.addListener(dom.loginForm, "submit", that._loginClick.bind(that)));

			//remove HTML from DOM
			dom.node.parentNode.removeChild(dom.node);

			//register to menu
			that.logout = that.logout.bind(that);
			app.menuRegister("Logout", that.logout, 100);
		},

		//******************* Module ***********************************************************//

		/**
		 * Mandatory method prescribed by UrlShortener.IModule interface
		 * @ignore
		 * @param {HTMLElement} node
		 */
		attach: function(node) {
			var that = this;
			node.appendChild(that._dom.node);
		},

		/**
		 * Mandatory method prescribed by UrlShortener.IModule interface
		 * @ignore
		 * @param {HTMLElement} node
		 */
		detach: function(node) {
			node.removeChild(this._dom.node);
		},

		/**
		 * Mandatory method prescribed by UrlShortener.Module parent class
		 * @ignore
		 * @param {Object} params
		 */
		registerHistory: function(params) {
			var that = this;
			that._app.pushToHistory({module: that.name}, "Login", "?" + that.name);
		},

		//*********************** end of Module methods ****************************************//

		/**
		 * login method called clicking  on login-button
		 * @param {Event} evt
		 */
		_loginClick: function(evt) {
			var that = this,
				elm = evt.target,
				dom = that._dom,
				hide = Shortener.hide,
				show = Shortener.show,
				status = true;

			evt.preventDefault();

			hide(dom.passwordError);
			hide(dom.usernameError);
			hide(dom.loginError);

			if (!dom.username.value) {
				show(dom.usernameError);
				status = false;
			}

			if (!dom.password.value) {
				show(dom.passwordError);
				status = false;
			}

			if (status) {
				this._app.requestUrl(
					"login",
					this._loginRequestCallback,
					"post",
					{username: dom.username.value, password: dom.password.value}
				);

				dom.username.value = "";
				dom.password.value = "";
			}
		},

		/**
		 * login method callback
		 * @param {String} txt response from Request
		 * @param {Number} status HTTP status code
		 */
		_loginRequestCallback: function(txt, status) {
			var that = this,
				dom = that._dom,
				hide = Shortener.hide,
				show = Shortener.show;

			if (status == 200) {
				setCookie("logged", 1, 1);
				that.makeEvent("login");
				//hide(dom.node);
			} else {
				setCookie("logged", 0, 10);
				show(dom.loginError);
			}
		},

		/**
		 * Call this method to logout user
		 */
		logout: function() {
			var that = this,
				app = that._app;

			//no menu item will be selected
			app.menuActivateItem();

			setCookie("logged", 0);
			app.requestUrl("server.php?method=logout",function(){}, "post");
			that.makeEvent("logout");
		},

		/**
		 * Checks if some user is logged in (cookie is set). This method didn't ask server but just check status against
		 * cookie which this module store. Asking server is not necessary, because server response with status 401 -
		 * unauthorized on every server data request.
		 *
		 * Asking server is postponed to "next" server request which returns 401 status when user is not logged.
		 */
		isLogged: function() {
			var that = this,
				logged = getCookie("logged");

			if(logged != null && +logged == 1) {
				setCookie("logged", 1, 1);
				return true;
			}
			return false;
		}
	});
}(UrlShortener));