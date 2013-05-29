(function(Shortener){
	var gel = document.getElementById.bind(document),
		FALSE = !1,
		TRUE = !FALSE,
		UrlDetail =

	/**
	 * @class This module generates detail page for given shortened URL. Page contains shorten URL to copy, than
	 * QR image code and also some statistics of usage.
	 * @name UrlShortener.UrlDetail
	 * @implements UrlShortener.Module
	 */
	Shortener.UrlDetail = Shortener.Module.extend(/** @lends UrlShortener.UrlDetail# */{
		/**
		 * Constructor
		 * @constructor
		 * @public
		 * @param {UrlShortener.Application} app
		 */
		constructor: function(app) {
			UrlDetail.super.constructor.apply(this, arguments);

			var that = this;
			that.name = "urlDetail";
			that._showUrlCallback = that._showUrlCallback.bind(that);
            that.init();
		},

		/**
		 * Mandatory property defined in Module interface
		 * @public
		 * @type String
		 * @name UrlShortener.UrlDetail#name
		 */
		//defined in constructor()

		//defined in UrlShortener.Module
		/*
		_ec: [],
		_dom: {},
		_app: null,*/

		/**
		 * Stored parent container given by attach method
		 * @type HTMLElement
		 */
		_parentNode: null,

		/**
		 * Reference to visible box (new url form)
		 * @type HTMLElement
		 */
		_showedBox: null,

		/**
		 * destructor called by application destructor
		 * @public
		 */
		destructor: function() {

		},

		/**
		 * init start listening on all elements
		 */
		init: function() {
			var that = this,
				dom = that._dom,
				hide = Shortener.hide;

			//url-result
			dom.node = gel("url-detail-box");
			dom.newUrl = gel("new-url");
			dom.newShortenUrl = gel("new-shorten-url");
			dom.newShortenUrlInput = gel("new-shorten-url-input");
			dom.qrCode = gel("qr-code");

			//statistics
			dom.statisticsBox = gel("statistics-box");
			dom.statsUrl = gel("stats-url");
			dom.statsClicks = gel("stats-clicks");
			dom.statsGlobalClicks = gel("stats-global-clicks");
			dom.detailLink = gel("detail-link");
			hide(dom.statisticsBox);

			dom.node.parentNode.removeChild(dom.node);
		},

		//******************* Module ***********************************************************//

		/**
		 * Mandatory method prescribed by UrlShortener.Module parent class
		 * @ignore
		 * @param {HTMLElement} node
		 * @param {Object} params
		 */
		attach: function(node, params) {
			var that = this;
			that._parentNode = node;
			node.appendChild(that._dom.node);
			that._showUrlDetail(params.hash);

			//no menu item will be selected
			that._app.menuActivateItem();
		},

		/**
		 * Mandatory method prescribed by UrlShortener.Module parent class
		 * @ignore
		 * @param {HTMLElement} node
		 */
		detach: function(node) {
			var that = this;
			that._parentNode = null;
			that._showedBox && node.removeChild(that._showedBox);
		},

		/**
		 * Mandatory method prescribed by UrlShortener.Module parent class
		 * @ignore
		 * @param {Object} params
		 */
		registerHistory: function(params) {
			var that = this;
			that._app.pushToHistory({module: that.name, params: params}, "URL detail", "?" + that.name + "&params=" + JSON.stringify({hash: params.hash}));
		},

		//*********************** end of Module methods ****************************************//

		/**
		 * Method obtains data from server for given hash
		 * @param {String} hash
		 */
		_showUrlDetail: function(hash) {
			this._app.requestUrl(
				"urlDetail&hash="+hash,
				this._showUrlCallback
			);
		},

		/**
		 * callback from XMLHttpRequest to show url detail data
		 * @param {String} txt response from Request
		 * @param {Number} status HTTP status code
		 */
		_showUrlCallback: function(txt, status) {
			var that = this,
				dom = that._dom,
				url,
				shorten_url;

			if (status == 200) {
				eval("var data = " + txt);
				if (data) {
					url = data.data.url;
					shorten_url = data.data.shorten_url;

					that._parentNode.appendChild(dom.node);
					that._showedBox = dom.node;

					dom.newUrl.href = url;
					dom.newUrl.innerHTML = url;
					dom.newShortenUrl.href = shorten_url;
					dom.newShortenUrl.innerHTML = shorten_url;
					dom.newShortenUrlInput.value = shorten_url;
					dom.qrCode.src = "http://qrcode.kaywa.com/img.php?s=6&d=" + encodeURIComponent(shorten_url);

					if (data.data.statistics) {
						Shortener.show(dom.statisticsBox);

						dom.statsUrl.innerHTML = data.data.url;
						dom.statsClicks.innerHTML = data.data.statistics.clicks;
						dom.statsGlobalClicks.innerHTML = data.data.statistics.global_clicks;
						dom.detailLink.innerHTML = data.data.statistics.statistics_url;
						dom.detailLink.href = data.data.statistics.statistics_url;
					}
				} else {
					alert("Something went wrong.");
				}
			}
		}
	});
}(UrlShortener));