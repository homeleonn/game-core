let server = new WSocket(`ws://192.168.0.101:8080`);
let app = new Application(server);	
user = new User(app, user);

connectToWSServer();
function connectToWSServer() {
	server.connectViaToken();
};

let $message 		= _('.message');
let $messages 		= _('#messages');
let $roomIdTextInput= _('#current-room');
let $roomButtons 	= _(`fieldset button`);
let $debug 			= _(`#debug`);
let $reconnect 		= _(`#reconnect`);


// _('#get-debug').on('click', () => {
// 	send({debug: true});
// });

// _('#event').on('click', () => {
// 	send({event: true});
// });

// _('#reconnect').on('click', () => {
// 	app.isReconnect = true;
// 	connectToWSServer();
// });


_('#sendmessage').on('click', () => {
	const msg = $message.val().trim();
	if (!msg.length) return;
	send({message: $message.val()});
	$message.val('');
	_('.message')[0].focus();
});

_('#sendmessage-form').on('submit', e => {
	e.preventDefault();
});

_('#chat-clear').on('click', e => {
	$messages.html('');
});

_('.location').on('click', 'polygon', function() {
	$(`.location-select .link.loc-${this.dataset.id}`).trigger('mousedown');
});





function send(data) {
	server.send(data);
}


let ww = $(window).width();
let wh = $(window).height();

// lication backlight
(() => {
	let locId;

	function toggleActive(locId) {
		$(`.${locId}`).each((i,item) => {
			item.classList.toggle('active');
		})
	}

	$('#location-wrapper').on('mouseenter mouseleave', '[class*="loc-"]',
		function(event) {
			if (event.type === 'mouseenter') {
				for (let item of this.classList) {
					if (/loc-\d+/.test(item)) {
						locId = item; break;
					}
				}
			}
			
			toggleActive(locId);
		}
	);
})();


let walkTimer;
function walk(secondsLeft, secondsFull) {
	clearTimeout(walkTimer);
	let html = 'Переход';
	let progress;
	let came = false;
	let $title = $('.location-pass .title');
	let $propgress = $('.location-pass .progress');
	const delay = 1;

	
	const step = function (secondsLeft, secondsFull) {
		let html = 'Переход';
		if (secondsLeft) {
			html += `...<br>${timer(secondsLeft)}`;
			progress = secondsLeft / secondsFull * 100;
		} else {
			html += ' возможен';
			progress = 0;
			came = true;
		}
		secondsLeft -= delay;

		$title.html(html);
		$propgress.css('width', progress + '%');

		if (!came) {
			walkTimer = setTimeout(() => {
				step(secondsLeft, secondsFull);
			}, delay * 1000)
		}
	};step(secondsLeft, secondsFull);
}


// Clock
(() => {
	function time() {
		const date = new Date();
		document.querySelector('#time').innerHTML = `${z(date.getHours())}:${z(date.getMinutes())}`;
	}

	time();
	setInterval(() => time(), 1000 * 10);
})();

// HP Restore
(() => {
	let curHp = 200;
	let maxHp = 800;
	let restoreSpeed = 1;
	let renderSpeed = 1 / 5;

	let HpRestore = hp(curHp, maxHp, restoreSpeed);
	HpRestore();
	const timer = setInterval(() => {
		if (HpRestore()) {
			clearInterval(timer);
		}
	}, 1000 / renderSpeed);

	function getTimeSeconds() {
		return (new Date()).getTime() / 1000;
	}
	
	function hp(curHp, maxHp, restoreSpeed = 1) {
		let lastRestore = getTimeSeconds();
		if (!localStorage.getItem('curHp')) {
			localStorage.setItem('curHp', curHp);
		}
		if (!localStorage.getItem('lastRestore')) {
			localStorage.setItem('lastRestore', lastRestore);
		}

		const COLOR_RED 	= '#993e3e';
		const COLOR_YELLOW 	= '#dddd42';
		const COLOR_GREEN 	= 'green';
		let line 			= document.querySelector('.hp-line');
		let hp 	 			= document.querySelector('.hp');
		let endRestoreFlag	= false;
		const restoreOneSecond = maxHp / (10 / restoreSpeed) / 60;

		return function() {
			const time 	= getTimeSeconds();
			const limeLeft = time - +localStorage.getItem('lastRestore');
			localStorage.setItem('lastRestore', lastRestore = time);
			localStorage.setItem('curHp', curHp = +localStorage.getItem('curHp') + limeLeft * restoreOneSecond);

			if (curHp >= maxHp) {
				curHp = maxHp;
				endRestoreFlag = true;
				['curHp', 'lastRestore'].forEach((item) => localStorage.removeItem(item));
			}
			hp.innerHTML = `${Math.round(curHp)} / ${maxHp}`;

			const curHpInPercent = curHp / maxHp * 100;
			const color = curHpInPercent < 33 ? COLOR_RED : (curHpInPercent < 66 ? COLOR_YELLOW : COLOR_GREEN);

			line.style.width = curHp / maxHp * 100 + '%';
			line.style.backgroundColor = color;

			return endRestoreFlag;
		}
	}
})();


// Resize chat
(() => {
	$('.resizer').click(resizeChat());
	

	function resizeChat() {
		const min 		= 150;
		const max 		= 600;
		const step 		= 50;
		let $footer		= $('footer');
		let $wrapper	= $('#location-wrapper');
		let height;

		return function(e) {
			if (!height) height = $footer.height();
			const directoion = e.target.className == 'up';

			if ((height >= max && directoion) || (height <= min && !directoion)) {
				return;
			}

			height += (directoion ? step : -step);

			$footer.css('height', height + 'px');
			$wrapper.css('margin-bottom', height + 'px');
		}
	}
})();

// LocalStorage
function LSsetIfNotExists(key, value, forceSet = false) {
	if (!localStorage.getItem(key) || forceSet) {
		localStorage.setItem(key, value);
	}
}

// Chat context menu
(() => {
	$('#messages, #room-users').on('contextmenu', '.name', contextMenu());

	function contextMenu() {
		$('body').append(`<div class="context-menu">
			<div class="name">.</div>
			<div class="info">Инфо</div>
			<div class="prv-msg">Приват</div>
			<div class="trade">Торговать</div>
		</div>`);

		let $contextMenu = $('.context-menu');
		let $name = $contextMenu.find('.name');

		function removeClickListener() {
			$contextMenu.hide();
		}

		return function(e)  {
			// console.log(e, $contextMenu.height());
			ctxHeight = $contextMenu.height();
			const yOffset = wh - e.pageY < ctxHeight - 20 ? wh - ctxHeight - 8 : e.pageY - 30;
			e.preventDefault();
			const name = $(this).text();
			$name.text(name);
			$contextMenu.show();
			$contextMenu.css({'top': yOffset, 'left': e.pageX - 40});
			$(document).one('click', removeClickListener);
		}
	}
})();

// Common
(() => {
	// let $messages = $('#messages');
	// let i = 20;

	// while (i--) {
	// 	$messages.append(`<div class="msg">
	// 		<div class="time">01:12</div>
	// 		<div class="name-wrapper">
	// 			[<span class="name">Admin</span>]
	// 		</div>
	// 		<div class="msg-text">Testing</div>
	// 	</div>`);
	// }


	// let $room = $('#room-users');
	// i = 20;

	// while (i--) {
	// 	$room.append(`<div class="user">
	// 		<div>
	// 			<img src="img/chat/prv_but.png" class="prv-msg" title="Отправить приватное сообщение">
	// 		</div>
	// 		<div>
	// 			<img src="img/clans/grandparents.png" class="clan" title="Прародители">
	// 		</div>
	// 		<div class="name-wrapper">
	// 			<span class="name">SpiderMan</span>
	// 			<span class="level">[0]</span>
	// 		</div>
	// 		<div>
	// 			<img src="img/user/get_info.gif" class="get-info" title="Информация о персонаже">
	// 		</div>
	// 	</div>`);
	// }
	
})();


// Transition
$('.location-select').on('mousedown', '.link', function() {
	transition.call(this);
});

let firstStep = true;
transition(0);

function transition(locationId = null) {
	let $wrapper = $('#location-wrapper');
	let $loader = $wrapper.find('.loader');
	let load = false;

	
	

	let $location = $wrapper.find('.location');
	let $locationSelect = $wrapper.find('.location-select');
	let $svg = $location.find('svg');

	if (locationId === null) {
		locationId = this.className.match(/loc-(\d+)/)[1];
	}

	// $.get('location.php', {locationId}, function(response) {
	// 	$location.children('.name').text(response.name);
	// 	$location.children('.map')
	// 		.on('load', () => {load = true;$loader.removeClass('icon-spin3');})
	// 		.attr('src', 'img/locations/' + response.image);
	// 	drawSelectLocations(response.select);
	// 	drawLocationsBorders($svg, response.locations);
	// }, 
	// 'json')
	// .fail(() => {load=true;console.log('Transition failure'); });


	if (firstStep) {
		firstStep = false;
	} else {
		user.chroom(locationId, response => {
			if (response !== user.CHROOM_ALLOWED) return;

			setTimeout(() => {
				if (!load) {
					$loader.addClass('icon-spin3');
				}
			}, 300);

			walk(10, 10);
			// $roomButtons.removeAttr('disabled');
			// _(this).attr('disabled', true);
			// $roomIdTextInput.text(toRoom);
		});
	}
	

	app.location = function(response) {
		$location.children('.name').text(response.name);
		app.$roomName.text(response.name);
		$location.children('.map')
			.on('load', () => {load = true;$loader.removeClass('icon-spin3');})
			.attr('src', 'img/locations/' + response.image);
		drawSelectLocations(response.select);
		drawLocationsBorders($svg, response.locations);
	}
}



function drawLocationsBorders($svg, locations) {
	let polygons = '';
	for (locationId in locations) {
		polygons += `<polygon data-id="${locationId}" class="loc-${locationId}" points="${locations[locationId]}" />`;
	}
	$svg.html(polygons);
}

function drawSelectLocations(select) {
	$('.location-block').hide();
	for (block in select) {
		let $block = $(`.location-block.${block}`);
		let $list = $block.children('.list');
		$list.html('');
		for (locId in select[block]) {
			draw(locId, select[block][locId], $list);
		}
		$block.show();
	}

	function draw(locId, name, to) {
		$('<div>').addClass(`link loc-${locId}`).text(name).appendTo(to);
	}
}