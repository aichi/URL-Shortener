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
}

/**
 * destructor called on window.unload
 */
UrlShortener.prototype.$destructor = function() {
	this.ec.forEach(JAK.Events.removeListener, JAK.Events);
	
	for (var p in this.dom) {
		this.dom[p] = null;
	}
}

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
}

/**
 * login method called clicking login-button submit
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
		var rq = new JAK.Request(JAK.Request.TEXT, {method: JAK.Request.POST});
		rq.setCallback(this, 'loginCallback');
		rq.send('server.php', {username: this.dom.username.value, password: this.dom.password.value});
	}
}

UrlShortener.prototype.loginCallback = function(txt, status) {
	if (status == 200) {
		this._hide(this.dom.loginBox);
		this._show(this.dom.menuBox);
		
		//@todo: dodelat metodu
		//this.showUrlList();
	} else {
		this._show(this.dom.loginError);
	}
}
	
UrlShortener.prototype._show = function(elm) {
	elm.style.display = '';
}

UrlShortener.prototype._hide = function(elm) {
	elm.style.display = 'none';
}