(function(Shortener){
	var gel = document.getElementById.bind(document),
		UrlList =

	/**
	 * @class This module generates table with list of shortened URLs. Every table row has two buttons to delete a URL and
	 * to get detail information generated by {@see UrlShortener.UrlDetail} module.
	 * @name UrlShortener.UrlList
	 * @implements UrlShortener.Module
	 */
	Shortener.UrlList = Shortener.Module.extend(/** @lends UrlShortener.UrlList */{
		/**
		 * Constructor
		 * @constructor
		 * @public
		 * @param {UrlShortener.Application} app
		 */
		constructor: function(app) {
			UrlList.super.constructor.apply(this, arguments);

			var that = this;
			that.name = "urlList";

			that._deleteLinkCallback = that._deleteLinkCallback.bind(that);
			that._showUrlListCallback = that._showUrlListCallback.bind(that);
			that.init();
		},

		/**
		 * Mandatory property defined in Module interface
		 * @public
		 * @type {String}
		 * @name UrlShortener.UrlList#name
		 */
		//defined in constructor()

		//defined in UrlShortener.Module
		/*
		_ec: [],
		_dom: {},
		_app: null,*/

		/**
		 * Hash table for storing links rendered in table, link shorten hash is a 'hash'
		 * @type {Object}
		 */
		_linkReference: {},

		/**
		 * destructor called on window.unload
		 */
		destructor: function() {
			var that = this,
				dom = that._dom,
				p;

			that._clearLinkReference();

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

			dom.node = gel("url-list-box");
			dom.urlListBody = gel("url-list-body");
			ec.push(Shortener.addListener(dom.urlListBody, "click", that._urlListClick.bind(that)));

			//remove HTML from DOM
			dom.node.parentNode.removeChild(dom.node);

			//register to menu
			that._menuClick = that._menuClick.bind(that);
			app.menuRegister("URL List", that._menuClick, 1);
		},

		//******************* Module ***********************************************************//
		/**
		 * Mandatory method prescribed by UrlShortener.Module parent class
		 * @ignore
		 * @param {HTMLElement} node
		 */
		attach: function(node) {
			var that = this;
			node.appendChild(that._dom.node);
			that.showUrlList();

			that._app.menuActivateItem(that._menuClick);
		},

		/**
		 * Mandatory method prescribed by UrlShortener.Module parent class
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
			that._app.pushToHistory({module: that.name}, "URL List", "?" + that.name);
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
		 * method handle click on URL list table and decide which method run. It depends
		 * on the click target (link)
		 * @param {Event} evt
		 */
		_urlListClick: function(evt) {
			evt.preventDefault();
			var elmClick = evt.target,
				hash,
				that = this,
				linkReference = that._linkReference;

			if (elmClick.nodeName.toLowerCase() == "a") {
				hash = elmClick.id.substr(1);
				if (linkReference[hash]) {
					that.urlListElmClick = elmClick; //save reference
					evt.stopPropagation();
					if (linkReference[hash].deleteLink == elmClick) {
						confirm("Really delete " + linkReference[hash].url + "?") && that._deleteLink(hash);
					} else {
						that._showDetail(hash);
					}
				}
			}
		},

		/**
		 * remove references on all link elements in URL table list
		 */
		_clearLinkReference: function() {
			var that = this,
				p,
				t,
				linkReference = that._linkReference;

			for (p in linkReference) {
				for (t in linkReference[p]) {
					linkReference[p][t] = null;
				}
			}
			linkReference = {};
		},

		/**
		 * user used Delete link
		 * @param hash
		 */
		_deleteLink: function(hash) {
			this._app.requestUrl(
				"deleteLink",
				this._deleteLinkCallback,
				"post",
				{hash: hash}
			);
		},

		/**
		 * delete link callback
		 * @param txt
		 * @param status
		 */
		_deleteLinkCallback: function(txt, status) {
			var that = this;
			if (status == 200) {
				eval("var data = " + txt);
				if (data && data.status == "ok") {
					that.showUrlList();
				} else {
					alert("Something went wrong.");
				}
			}
		},

		/**
		 * user used Statistics link
		 * @param hash
		 */
		_showDetail: function(hash) {
			this._app.activateModule("urlDetail", {hash: hash});
		},

		/**
		 * render table with URL list - preparation
		 */
		showUrlList: function() {
//			Shortener.show(this._dom.throbberBox);
			this._app.requestUrl(
				"urlList",
				this._showUrlListCallback
			);
		},

		/**
		 * callback from server request data
		 */
		_showUrlListCallback: function(txt, status) {
//			Shortener.hide(this._dom.throbberBox);
			Shortener.show(this._dom.node);

			if (status == 200) {
				eval("var data = " + txt);
				this._renderUrlList(data);
			}
		},

		/**
		 * data rendering to table
		 */
		_renderUrlList: function(urls) {
			var that = this,
				dom = that._dom,
				urlListBody = dom.urlListBody,
				i,
				length = urls.list ? urls.list.length : 0,
				item,
				row,
				td1,
				td2,
				td3,
				shortUrl,
				dlink,
				spacer,
				slink,
				doc = document,
				linkReference = that._linkReference;

			//remove all old childnodes
			if (urlListBody.hasChildNodes()) {
				while (urlListBody.childNodes.length >= 1){
					urlListBody.removeChild(urlListBody.firstChild);
				}
			}

			//rendering new childnodes
			for (i = 0; i < length; i++) {
				item = urls.list[i];

				row = doc.createElement("tr");
				td1 = doc.createElement("td");
				td1.innerHTML = item.originalUrl;

				td2 = doc.createElement("td");
				shortUrl = doc.createElement("span");
				shortUrl.innerHTML = urls.url + item.idUrlShorten;
				td2.appendChild(shortUrl);

				td3 = doc.createElement("td");
				dlink = doc.createElement("a");
				dlink.id = "d" + item.idUrlShorten;
				dlink.className = "btn btn-danger btn-small";
				dlink.href = "#" + item.idUrlShorten;
				dlink.innerHTML = "Delete";
				spacer = doc.createTextNode(" ");
				slink = doc.createElement("a");
				slink.id = "s" + item.idUrlShorten;
				slink.className = "btn btn-info btn-small";
				slink.href = "&params=" + JSON.stringify({hash: item.idUrlShorten});
				slink.innerHTML = "Detail &amp; statistics";
				td3.appendChild(dlink);
				td3.appendChild(spacer);
				td3.appendChild(slink);

				row.appendChild(td1);
				row.appendChild(td2);
				row.appendChild(td3);
				urlListBody.appendChild(row);

				linkReference[item.idUrlShorten] = {deleteLink: dlink, detailLink: slink, url: item.originalUrl};
			}
		}
	});
}(UrlShortener));