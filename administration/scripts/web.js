UrlShortener = JAK.ClassMaker.makeClass({
	'NAME' : 'UrlShortener',
	'VERSION': '1.0'
});

/**
 * constructor
 */
UrlShortener.prototype.$constructor = function() {
	//events id cache
	this.ec = [];
	//dom elements cache
	this.dom = {};
	
	this.init();
};

/**
 * destructor called on window.unload
 */
UrlShortener.prototype.$destructor = function() {
	this.ec.forEach(JAK.Events.removeListener, JAK.Events);
	
	for (var p in this.dom) {
		this.dom[p] = null;
	}
};

/**
 * init start listenning on all elements
 */
UrlShortener.prototype.init = function() {
	this.ec.push(JAK.Events.addListener(window, 'unload', this, '$destructor'));
	
	//menu
	this.dom.menuBox = JAK.gel('menu-box');
	this.dom.menuItemUrlList = JAK.gel('menu-url-list');
	this.dom.menuItemNewUrl = JAK.gel('menu-new-url');
	this.dom.menuItemLogout = JAK.gel('menu-logout');
	
	this._hide(this.dom.menuBox);
	
	//this.ec.push(JAK.Events.addListener(this.dom.menuItemUrlList, 'click', this, 'showUrlListClick'));
	//this.ec.push(JAK.Events.addListener(this.dom.menuItemNewUrl, 'click', this, 'showNewItemFormClick'));
	//this.ec.push(JAK.Events.addListener(this.dom.menuItemLogout, 'click', this, 'logoutClick'));
	
	
	//login
	this.dom.loginBox = JAK.gel('login-box');
	this.dom.username = JAK.gel('username');
	this.dom.password = JAK.gel('password');
	this.dom.loginButton = JAK.gel('login-button');
	this.dom.usernameError = JAK.gel('username-error');
	this.dom.passwordError = JAK.gel('password-error');
	this.dom.loginError = JAK.gel('login-error');
	
	this._hide(this.dom.usernameError);
	this._hide(this.dom.passwordError);
	this._hide(this.dom.loginError);
	
	this.ec.push(JAK.Events.addListener(this.dom.loginButton, 'click', this, 'loginClick'));
	
	
	//throbber
	this.dom.throbberBox = JAK.gel('throbber-box');
	this._hide(this.dom.throbberBox);
	
	//url-list
	this.dom.urlListBox = JAK.gel('url-list-box');
	this.dom.urlListBody = JAK.gel('url-list-body');
	
	this._hide(this.dom.urlListBox);
};

/**
 * show element
 * @param elm
 */
UrlShortener.prototype._show = function(elm) {
	elm.style.display = '';
};

/**
 * hide element
 * @param elm
 */
UrlShortener.prototype._hide = function(elm) {
	elm.style.display = 'none';
};

/**
 * login method called clicking login-button submit
 * @param e
 * @param elm
 */
UrlShortener.prototype.loginClick = function(e, elm) {
	JAK.Events.cancelDef(e);
	
	this._hide(this.dom.passwordError);
	this._hide(this.dom.usernameError);
	this._hide(this.dom.loginError);
	
	var status = true;
	
	if (!this.dom.username.value) {
		this._show(this.dom.usernameError);
		status = false;
	}
	
	if (!this.dom.password.value) {
		this._show(this.dom.passwordError);
		status = false;
	}
	
	if (status) {
		var rq = new JAK.Request(JAK.Request.TEXT, {method: 'post'});
		rq.setCallback(this, '_loginCallback');
		rq.send('server.php?page=login', {username: this.dom.username.value, password: this.dom.password.value});
	}
};

/**
 * login method callback
 * @param txt
 * @param status
 */
UrlShortener.prototype._loginCallback = function(txt, status) {
	if (status == 200) {
		this._hide(this.dom.loginBox);
		this._show(this.dom.menuBox);
		
		this.showUrlList();
		//show menu
		this._show(this.dom.menuBox);
		//hide loginbox
		this._hide(this.dom.loginBox);
	} else {
		this._show(this.dom.loginError);
	}
};
	
/**
 * render table with URL list
 */
UrlShortener.prototype.showUrlList = function() {
	this._show(this.dom.throbberBox);
	
	var rq = new JAK.Request(JAK.Request.TEXT);
	rq.setCallback(this, '_showUrlListCallback');
	rq.send('server.php?page=urlList');
};

UrlShortener.prototype._showUrlListCallback = function(txt, status) {
	this._hide(this.dom.throbberBox);
	this._show(this.dom.urlListBox);
	if (status == 200) {
		eval('var data = ' + txt);
		this._renderUrlList(data);
	} else {
		console.log('logout');
	}
};


UrlShortener.prototype._renderUrlList = function(urls) {
	for (var i = 0; i < urls.length; i++) {
		var row = JAK.cel('tr');
		var td1 = JAK.mel('td', {innerHTML: urls[i].originalUrl});
		var td2 = JAK.cel('td');
		
		var link = JAK.mel('a', {href: '#' + urls[i].idUrlShorten, innerHTML: 'Delete' });
	
		JAK.DOM.append([this.dom.urlListBody, row],[row, td1, td2], [td2, link]);
	}
};
