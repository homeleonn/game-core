class User {
	constructor(app, user) {
		this.app = app;
		this.id = user.id;
		this.login = user.login;
		this.level = user.level;
		this.loc = user.loc;
		this.trans_timeout = user.trans_timeout;
		this.CHLOC_ALLOWED = 1;
		this.CHLOC_DENIED = 0;
	}

	chloc(toLoc, callback = null) {
    	this.app.chloc(toLoc, callback);
    }
}