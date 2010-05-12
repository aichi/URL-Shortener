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
	
	this.showedBox = null;
	
	this.init();
};

/**
 * destructor called on window.unload
 */
UrlShortener.prototype.$destructor = function() {
	this.ec.forEach(JAK.Events.removeListener, JAK.Events);
	
	this.showedBox = null;
	
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
	
	this.ec.push(JAK.Events.addListener(this.dom.menuItemUrlList, 'click', this, 'showUrlListClick'));
	this.ec.push(JAK.Events.addListener(this.dom.menuItemNewUrl, 'click', this, 'newItemFormClick'));
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
	
	this.showedBox = this.dom.loginBox;
	
	
	//throbber
	this.dom.throbberBox = JAK.gel('throbber-box');
	this._hide(this.dom.throbberBox);
	
	
	//url-list
	this.dom.urlListBox = JAK.gel('url-list-box');
	this.dom.urlListBody = JAK.gel('url-list-body');
	this._hide(this.dom.urlListBox);
	
	
	//new-url
	this.dom.newUrlBox = JAK.gel('new-url-box');
	this.dom.url = JAK.gel('url');
	this.dom.hash = JAK.gel('hash');
	this.dom.urlError = JAK.gel('url-error');
	this.dom.hashError = JAK.gel('hash-error');
	this.dom.hashUniqueError = JAK.gel('hash-unique-error');
	this.dom.newUrlGlobalError = JAK.gel('new-url-global-error');
	this.dom.newUrlButton = JAK.gel('new-url-button');
	
	this._hide(this.dom.newUrlBox);
	this._hide(this.dom.urlError);
	this._hide(this.dom.hashError);
	this._hide(this.dom.hashUniqueError);
	
	this.ec.push(JAK.Events.addListener(this.dom.newUrlButton, 'click', this, 'newUrlClick'));
	this.ec.push(JAK.Events.addListener(this.dom.hash, 'change', this, 'checkHash'));
	this.ec.push(JAK.Events.addListener(this.dom.hash, 'keyup', this, 'checkHash'));
	
	//new-url-result
	this.dom.newUrlResultBox = JAK.gel('new-url-result-box');
	this.dom.newUrl = JAK.gel('new-url');
	this.dom.newShortenUrl = JAK.gel('new-shorten-url');
	this.dom.newShortenUrlInput = JAK.gel('new-shorten-url-input');
    this.dom.qrCode = JAK.gel('qr-code');
	
	this._hide(this.dom.newUrlResultBox);
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

/*----------------------------LOGIN----------------------------*/

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
	
		this._hide(this.showedBox);
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

/*--------------------------Show URLs-------------------------*/

UrlShortener.prototype.showUrlListClick = function(e, elm){
	JAK.Events.cancelDef(e);
	this.showUrlList();
};


/**
 * render table with URL list - preparation
 */
UrlShortener.prototype.showUrlList = function() {
	this._show(this.dom.throbberBox);
    this._hide(this.showedBox);
	
	var rq = new JAK.Request(JAK.Request.TEXT);
	rq.setCallback(this, '_showUrlListCallback');
	rq.send('server.php?page=urlList');
};

/**
 * callback from server request data 
 */
UrlShortener.prototype._showUrlListCallback = function(txt, status) {
	this._hide(this.dom.throbberBox);
	this._show(this.dom.urlListBox);
	this.showedBox = this.dom.urlListBox;
	
	if (status == 200) {
		eval('var data = ' + txt);
		this._renderUrlList(data);
	} else {
		//@todo logout
		console.log('logout');
	}
};

/**
 * data rendering to table
 */
UrlShortener.prototype._renderUrlList = function(urls) {
    //remove all old childnodes
    if ( this.dom.urlListBody.hasChildNodes() ) {
        while ( this.dom.urlListBody.childNodes.length >= 1 ){
            this.dom.urlListBody.removeChild( this.dom.urlListBody.firstChild );
        }
    }

    //rendering new childnodes
	for (var i = 0; i < urls.length; i++) {
		var row = JAK.cel('tr');
		var td1 = JAK.mel('td', {innerHTML: urls[i].originalUrl});
		var td2 = JAK.cel('td');
		
		var link = JAK.mel('a', {href: '#' + urls[i].idUrlShorten, innerHTML: 'Delete'});
	
		JAK.DOM.append([this.dom.urlListBody, row],[row, td1, td2], [td2, link]);
	}
};

/*--------------------------New URL--------------------------*/
/**
 * action after menu item click
 */
UrlShortener.prototype.newItemFormClick = function(e, elm) {
	JAK.Events.cancelDef(e);
	this.newItem();
};

/**
 * show form, where you can add new url
 */
UrlShortener.prototype.newItem = function() {
	this._hide(this.showedBox);
	this._show(this.dom.newUrlBox);
	this.showedBox = this.dom.newUrlBox;

    this.dom.hash.value = '';
    this.dom.url.value = '';
};

/**
 * action on save button
 */
UrlShortener.prototype.newUrlClick = function(e, elm) {
	var result = true;
	
	this._hide(this.dom.urlError);
	this._hide(this.dom.hashError);
	this._hide(this.dom.hashUniqueError);
	this._hide(this.dom.newUrlGlobalError);
	
	if (this.dom.url.value.length == 0) {
		result = false;
		this._show(this.dom.urlError);
	}
	
	if (!this._isUrl(this.dom.url.value)) {
		result = false;
		this._show(this.dom.urlError);
	}
	
	if (!this._isHash(this.dom.hash)) {
		result = false;
		this._show(this.dom.hashError);
	}
	
	if (result) {
		this._show(this.dom.throbberBox);
		
		var rq = new JAK.Request(JAK.Request.TEXT, {method: 'post'});
		rq.setCallback(this, '_newUrlListCallback');
		rq.send('server.php?page=newUrl', {url: this.dom.url.value, hash: this.dom.hash.value});
	}
};

UrlShortener.prototype._newUrlListCallback = function(txt, status) {
	this._hide(this.dom.throbberBox);
	if(status == 200) {
		eval('var data = ' + txt);
		//url saved, show result
		if (data.status == 'ok') {
			this._hide(this.showedBox);
			this._show(this.dom.newUrlResultBox);
			this.showedBox = this.dom.newUrlResultBox;
			
			
			this.dom.newUrl.href = data.data.url;
			this.dom.newUrl.innerHTML = data.data.url;
			this.dom.newShortenUrl.href = data.data.shorten_url;
			this.dom.newShortenUrl.innerHTML = data.data.shorten_url;
			this.dom.newShortenUrlInput.value = data.data.shorten_url;
            this.dom.qrCode.src = 'http://qrcode.kaywa.com/img.php?s=6&d=' + encodeURIComponent(data.data.shorten_url);
		//url not saved, show errors
		} else {
            if (data.errorText) {
                this.dom.newUrlGlobalError.innerHTML = data.errorText;
                this._show(this.dom.newUrlGlobalError);
            }
            if (data.errors) {
                for (var i = 0; i < data.errors.length; i++) {
                    var key = data.errors[i];
                    if (key == 'emptyurl' || key == 'invalidurl') {
                        this._show(this.dom.urlError);
                    }
                    if (key == 'nonuniquehash') {
                        this._show(this.dom.hashUniqueError);
                    }
                    if (key == 'invalidhash') {
                        this._show(this.dom.hashError);
                    }
                }
            }
		}
	} else {
		//@todo logout
		console.log('logout');
	}
};

/**
 * test if string is valid URL
 */
UrlShortener.prototype._isUrl = function(s) {
	var regexp = /(ftp|ftps|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
	return regexp.test(s);
};

/**
 * test if string is valid Hash
 */
UrlShortener.prototype._isHash = function(s) {
	var regexp = /[a-zA-z0-9_\-]*/;
	return regexp.test(s);
};

/**
 * online validate hash uniquicity 
 */
UrlShortener.prototype.checkHash = function(e, elm) {
	var rq = new JAK.Request(JAK.Request.TEXT);
	rq.setCallback(this, '_checkHashCallback');
	rq.send('server.php?page=checkHash&hash=' + elm.value);
};

UrlShortener.prototype._checkHashCallback = function(txt, status) {
	if (status == 200) {
		eval('var data = ' + txt);
		if (data && data.unique) {
			this._hide(this.dom.hashUniqueError);
		} else {
			this._show(this.dom.hashUniqueError);
		}
	}
};
