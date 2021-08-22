const DUPLICATE = '1';

class Application {
	constructor(server) {
		this.server = server;
		this.$messages = _('#messages');
		this.$room = _('#room-users');
		this.$roomName = _('#location-caption > .name');
		this.$roomUsersCount = _('#location-caption > .count');
		this.roomUsersCount = 0;
		this.init = false;
		this.isReconnect = false;
		this.callbacks = [];
		this.wsBind();
	}

	message(response) {
		cl(response.data);
		switch (response.data) {
		case DUPLICATE:
			if (this.isReconnect) {
				this.isReconnect = false;
			} else {
	    		this.exit();
			}
			
			return;
		break;
		}

		let data = JSON.parse(response.data);
		
		console.log(data);

		//['error', 'exit', 'chroom']
		let action = Object.keys(data)[0], callback;
		if (action != 'message') {
			callback = this.callbacks[action] || this[action];
			if (callback) {
				callback.call(this, data[action]);
			}
		}
		
	    this.handleMessage(data.message);
	}

	open() {
		// cl(arguments);

		// setTimeout(() => send({debug: true}), 1000);
		this.send({debug: true});
		// cl(1, this.send)
	}

	error() {
		// cl(arguments);
	}

	close() {
		// cl(arguments);
	}

	append(msg) {
        this.$messages.append(`<div class="msg">${date('H:i:s')} ${msg}</div>`);
    }

    send(data) {
    	this.server.send(data);
    }

    chroom(toRoom, callback = null) {
    	if (callback) this.callbacks['chroom'] = callback;
    	this.send({chroom: toRoom});
    }

    room_add(user) {
    	this.userAdd(user)
    	this.setRoomUsersCount(++this.roomUsersCount);
    }

    room_leave(user) {
    	_(`#user-${user.id}`).remove();
    	this.setRoomUsersCount(--this.roomUsersCount);
    }

    room_users(users) {
    	this.setRoomUsersCount(this.roomUsersCount = users.length);
    	this.$room.html('');
    	users.forEach(this.userAdd.bind(this));
    }

    setRoomUsersCount(count) {
    	this.$roomUsersCount.text(count);
    }

    userAdd(user) {
    	this.$room.append( `<div class="user" id="user-${user.id}">
			<div>
				<img src="img/chat/prv_but.png" class="prv-msg" title="Отправить приватное сообщение">
			</div>
			<div>
				<img src="img/clans/grandparents.png" class="clan" title="Прародители">
			</div>
			<div class="name-wrapper">
				<span class="name">${user.name}</span>
				<span class="level">[0]</span>
			</div>
			<div>
				<img src="img/user/get_info.gif" class="get-info" title="Информация о персонаже">
			</div>
		</div>`);
    }



    scrollDown() {
		this.$messages[0].scrollTop = this.$messages[0].scrollHeight;
	}

    handleMessage(message) {
		// cl(arguments);
    	switch (typeof message) {
	    	case 'string': this.append(message);break;
	    	case 'object':
	            this.append(`${message.from}: ${message.text}`);
	            this.scrollDown();
	    	break;
	    }
    }

    exit(isExit = true) {
    	if (isExit) {
    		// _('#logout').trigger('click');
    		_('body').html('<h1>обнаружено дублирование окна</h1>');
    		return true;
    	}

    	return false;
    }

	wsBind() {
		let ws = window.WebSocket;
		let app = this;

		window.WebSocket = function(a, b) {
			let that = b ? new ws(a, b) : new ws(a);
			['open', 'message', 'error', 'close'].forEach(event => that.addEventListener(event, app[event].bind(app)));
			return that;
		};

		window.WebSocket.prototype=ws.prototype; 
	}
}