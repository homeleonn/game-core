class WSocket
{
	constructor(to, token, tryConnection = 3) {
		this.to = to;
		this.token = token;
		this.tryConnection = tryConnection;
	}

	connect() {
		if (!this.tryConnection--) return;
		this.server = new WebSocket(`${this.to}${this.token}`);
	}

	connectViaToken() {
		$.get('/wsToken', res => {
			this.token = '/' + res;
	 		if (res == 'error') {
	 			throw new Error('Session error');
	 		} else {
	 			this.connect();
	 		}
	 	});
	}

	send(data) {
		this.server.send(JSON.stringify(data));
	}
}