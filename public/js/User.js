class User {
	constructor(app, name, room) {
		this.app = app;
		this.name = user.name;
		this.room = user.room;
		this.transitionTimeout = user.transitionTimeout;
		this.CHROOM_ALLOWED = 1;
		this.CHROOM_DENIED = 0;
	}

	chroom(toRoom, callback = null) {
    	this.app.chroom(toRoom, callback);
    }
}