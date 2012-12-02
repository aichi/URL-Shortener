(function(Shortener){
	var gel = document.getElementById.bind(document),
		FALSE = !1,
		TRUE = !FALSE,
		NewUrl =

	/**
	 * @class This module generates form for creating new shortened URL. It also checked
	 * if given optional hash is not used by other shortened URLs. When everything
	 * is fine all data are saved on server and user is redirected on {@see UrlShortener.UrlDetail} module.
	 * @name UrlShortener.NewUrl
	 * @implements UrlShortener.Module
	 */
	Shortener.NewUrl = Shortener.Module.extend(/** @lends UrlShortener.NewUrl# */{
		/**
		 * Constructor
		 * @constructor
		 * @public
		 * @param {UrlShortener.Application} app
		 */
		constructor: function(app) {
			NewUrl.super.constructor.apply(this, arguments);

			var that = this;
			that.name = "newUrl";

			that._newUrlListCallback = that._newUrlListCallback.bind(that);
			that._checkHashCallback = that._checkHashCallback.bind(that);
			that.init();
		},

		/**
		 * Mandatory property defined in Module interface
		 * @public
		 * @type {String}
		 * @name UrlShortener.NewUrl#name
		 */
		//defined in constructor()

		//defined in UrlShortener.Module
		/*
		_ec: [],
		_dom: {},
		_app: null,*/

		/**
		 * Stored parent container given by attach method
		 * @type {HTMLElement}
		 */
		_parentNode: null,

		/**
		 * Reference to visible box (new url form)
		 * @type {HTMLElement}
		 */
		_showedBox: null,

		/**
		 * destructor called by {@see UrlShortener.Application#destructor}
		 * @public
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

			//new-url
			dom.node = gel("new-url-box");
			dom.url = gel("url");
			dom.hash = gel("hash");
			dom.urlError = gel("url-error");
			dom.hashError = gel("hash-error");
			dom.hashUniqueError = gel("hash-unique-error");
			dom.newUrlGlobalError = gel("new-url-global-error");
			dom.newUrlButton = gel("new-url-button");
			dom.newUrlSuccess = gel("new-url-success");

			dom.node.parentNode.removeChild(dom.node);
			hide(dom.node);
			hide(dom.urlError);
			hide(dom.hashError);
			hide(dom.hashUniqueError);
			hide(dom.newUrlSuccess);

			ec.push(Shortener.addListener(dom.newUrlButton, "click", that._newUrlFormSubmitClick.bind(that)));
			ec.push(Shortener.addListener(dom.hash, "change", that._checkHash.bind(that)));
			ec.push(Shortener.addListener(dom.hash, "keyup", that._checkHash.bind(that)));

			//register to menu
			that._menuClick = that._menuClick.bind(that);
			app.menuRegister("New URL", that._menuClick, 2);
		},

		//******************* Module ***********************************************************//

		/**
		 * Mandatory method prescribed by UrlShortener.IModule interface
		 * @ignore
		 * @param {HTMLElement} node
		 */
		attach: function(node) {
			var that = this,
				dom = that._dom;

			that._parentNode = node;
			node.appendChild(dom.node);
			that._showNewUrlForm();
			Shortener.hide(dom.newUrlSuccess);

			that._app.menuActivateItem(that._menuClick);
		},

		/**
		 * Mandatory method prescribed by UrlShortener.IModule interface
		 * @ignore
		 * @param {HTMLElement} node
		 */
		detach: function(node) {
			var that = this;
			that._parentNode = null;
			node.removeChild(that._showedBox);
		},

		/**
		 * Mandatory method prescribed by UrlShortener.Module parent class
		 * @ignore
		 * @param {Object} params
		 */
		registerHistory: function(params) {
			var that = this;
			that._app.pushToHistory({module: that.name}, "New URL", "?" + that.name);
		},

		//*********************** end of Module methods ****************************************//

		/**
		 * Method called on menu item click
		 */
		_menuClick: function() {
			var that = this,
				app = that._app;
			app.activateModule(that);
		},

		/**
		 * Show form, where user can add new URL
		 */
		_showNewUrlForm: function() {
			var dom = this._dom,
				node = dom.node;
			Shortener.show(node);
			this._showedBox = node;

			dom.hash.value = "";
			dom.url.value = "";
		},

		/**
		 * Action attached on save button click
		 * @param {Event} evt
		 */
		_newUrlFormSubmitClick: function(evt) {
			var result = TRUE,
				that = this,
				dom = that._dom,
				elm = evt.target,
				hash = dom.hash.value,
				url = dom.url.value;

			Shortener.hide(dom.urlError);
			Shortener.hide(dom.hashError);
			Shortener.hide(dom.hashUniqueError);
			Shortener.hide(dom.newUrlGlobalError);
			Shortener.hide(dom.newUrlSuccess);

			if (url.length == 0 || !that._isUrl(url)) {
				result = FALSE;
				Shortener.show(dom.urlError);
			}

			if (!that._isHash(hash)) {
				result = FALSE;
				Shortener.show(dom.hashError);
			}

			if (result) {
//				Shortener.show(dom.throbberBox);
				this._app.requestUrl(
					"newUrl",
					this._newUrlListCallback,
					"post",
					{url: url, hash: hash}
				);
			}
		},

		/**
		 * Callback called by XMLHttpRequest triggered by save button click
		 * @param {String} txt response from Request
		 * @param {Number} status HTTP status code
		 */
		_newUrlListCallback: function(txt, status) {
			var that = this,
				dom = that._dom,
				parentNode = that._parentNode,
				i,
				key;

//			Shortener.hide(dom.throbberBox);
			if(status == 200) {
				eval("var data = " + txt);
				//url saved, show result
				if (data.status == "ok") {
					//It is now showing detail url page, otherwise comment out next line and outcomment this line
					//Shortener.show(dom.newUrlSuccess);

					that._app.activateModule("urlDetail", {hash: data.data.hash});
				//url not saved, show errors
				} else {
					if (data.errorText) {
						dom.newUrlGlobalError.innerHTML = data.errorText;
						Shortener.show(dom.newUrlGlobalError);
					}
					if (data.errors) {
						for (i = 0; i < data.errors.length; i++) {
							key = data.errors[i];
							if (key == "emptyurl" || key == "invalidurl") {
								Shortener.show(dom.urlError);
							}
							if (key == "nonuniquehash") {
								Shortener.show(dom.hashUniqueError);
							}
							if (key == "invalidhash") {
								Shortener.show(dom.hashError);
							}
						}
					}
				}
			}
		},

		/**
		 * Test if string is valid URL
		 * @param {String} s
		 * @returns {Boolean}
		 */
		_isUrl: function(s) {
			var regexp = /(ftp|ftps|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
			return regexp.test(s);
		},

		/**
		 * Test if string is valid Hash
		 * @param {String} s
		 * @returns {Boolean}
		 */
		_isHash: function(s) {
			var regexp = /[a-zA-z0-9_\-\.]*/;
			return regexp.test(s);
		},

		/**
		 * Online validate hash uniqueness on keyup in hash input filed
		 * @param {Event} evt
		 */
		_checkHash: function(evt) {
			this._app.requestUrl(
				"checkHash&hash=" + evt.target.value,
				this._checkHashCallback
			);
		},

		/**
		 * Callback called by XMLHttpRequest which check if hash is unique.
		 * @param {String} txt response from Request
		 * @param {Number} status HTTP status code
		 */
		_checkHashCallback: function(txt, status) {
			if(status == 200) {
				eval("var data = " + txt);
				if (data && data.unique) {
					Shortener.hide(this._dom.hashUniqueError);
				} else {
					Shortener.show(this._dom.hashUniqueError);
				}
			}
		}
	});
}(UrlShortener));