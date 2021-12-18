function z(num) {
	return +num > 9 ? num : '0' + num;
}

function timer(seconds, callback = false) {
	const hours = seconds / 3600;
	seconds = seconds % 3600;
	const minutes = seconds / 60;
	seconds = seconds % 60;

	const time = [hours, minutes, seconds].map((item) => {
		return z(Math.floor(item));
	});


	return time.join(':');
}

function fnOnTimeout(callback, delay = 1000) {
	var timeout = false;

	return function() {
		if (!timeout) {
			timeout = true;

			setTimeout(() => {
				callback(...arguments);
				timeout = false;
			}, delay);
		}
	}
}

function cl(){
	console.log(...arguments, ' / ' + new Error().stack.split(/\n/)[1].split('/').pop());
}

function getCookie(name) {
	let matches = document.cookie.match(new RegExp(
		"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	));
	return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(name, value, options = {}) {
	options = {
		path: '/',
		...options
	};

	if (options.expires instanceof Date) {
		options.expires = options.expires.toUTCString();
	}

	let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

	for (let optionKey in options) {
		updatedCookie += "; " + optionKey;
		let optionValue = options[optionKey];
		if (optionValue !== true) {
			updatedCookie += "=" + optionValue;
		}
	}

	document.cookie = updatedCookie;
}

function date(format, date = new Date()) {
	if (format === 'H:i:s') return date.toLocaleTimeString();

	let formats = {
		"H": 'Hours',
		"i": 'Minutes',
		"s": 'Seconds',
		"Y": 'FullYear',
		"y": 'FullYear',
		"m": 'Month',
		"d": 'Day'
	};

	return format.replace(/([YymdHis])/g, item => {
		let s = z(date['get' + formats[item]]());
		return item === 'y' ? s.toString().substr(2, 2) : s;
	});
}

(() => {

function cl(){
	console.log(...arguments, ' / ' + new Error().stack.split(/\n/)[1].split('/').pop());
}

function add(el, event, callback) {
	if (!isFunction(callback)) {
		throw 'Parameter callback must be callable';
	}

	el.addEventListener(event, callback, false);
}

function isFunction(guess, exception = false) {
	const flag = typeof guess === 'function';

	if (exception && !flag) {
		throw `${guess} don't callable`;
	}

	return flag;
}


class Core {
	e(callback) {
		return this.each(callback, true);
	}

	append(s) {
		this.e(el => {
			el.innerHTML += s;
		});
	}

	val(s = null) {
		return this.ret('value', s);
		// return s ? this.ret('value', s) : this;
	}

	children(selector) {
		return _(selector, this[0]);
	}

	toArray(raw = null) {
		return Array.prototype.slice.call(raw ? raw : this);
	}

	css(key, value = null) {
		if (typeof key !== 'object') {
			if (!value) return this[0].style[key];
			key = {[key]: value};
		}
		this.e(el => {
			for (let k in key) {
				el.style[k] = key[k];
			}
		});

		return this;
	}

	hide() {
		this.css({'display': 'none'});
		return this;
	}

	show() {
		this.css({'display': ''});
		return this;
	}

	addClass(classNames) {
		this.changeClass(classNames, 'add');
	}

	removeClass(classNames) {
		this.changeClass(classNames, 'remove');
	}

	toggleClass(classNames) {
		this.changeClass(classNames, 'toggle');
	}

	changeClass(classNames, action) {
		this.e(el => {
			classNames.split(' ').forEach((item) => {
				el.classList[action](item);
			})
		})
	}

	height() {
		return this[0].scrollHeight;
	}

	width() {
		return this[0].scrollWidth;
	}

	on(event, childSelector, callback = null) {
		if (typeof childSelector == "function") {
			this.e(el => add(el, event, childSelector));
		} else {
			this.e(el => {
				add(el, event, e =>  {
					let _selector = _(childSelector, el);
					if (_selector) {
						_selector.each((i, item) => {
							if (e.target == item) {
								callback.call(item, e);
							}
						});
					}
					
				});
			});
		}
	}

	text(s = null) {
		return this.ret('innerText', s);
	}

	html(s = null) {
		return this.ret('innerHTML', s);
	}

	ret(type, s = null) {
		return s === null ? this.toArray().reduce((accumulator, currentValue) => accumulator + currentValue[type], '')
				  		  : this.e(el => el[type] = s);
	}

	attr(name, value) {
		this.e(el => el.setAttribute(name, value));
	}

	removeAttr(name) {
		this.e(el => el.removeAttribute(name));
	}

	remove() {
		this[0].remove();
	}

	focus() {
		this[0].focus();return this;
	}

	data(label) {
		return this[0].dataset;
	}

	each(callback, order = null) {
		for (let prop in this) {
			if (!isNaN(prop)) {
				if (false === callback(...(!order ? [+prop, this[prop]] : [this[prop], +prop]))) break;
			}
		}

		return this;
	}

	test() {
		// this.each((i, el) => cl(el))
		this.e(el => cl(el, 1111))
	}

	animate(props, dur, easing, callback) {
		// Core.__((el) => cl(el));
	}

	trigger(e) {
		var event = new Event(e);
		this.e(element => {
			if (element.hasOwnProperty(e)) element[e]();
			element.dispatchEvent(event);
			cl(element, element.dispatchEvent, event);
		});
	}
}

class Library extends Core {
	constructor(selector) {
		super();
	}
}

function factory() {
	let prevSelector;
	let prevInstance;

	return function(selector, context = null) {
		if (isFunction(selector)) {
			return add(context || window, 'load', selector);
		}

		// if (prevSelector === selector) return prevInstance;
		// prevSelector = selector;

		let o, length;
		if (selector instanceof HTMLElement ||
			selector instanceof Document ||
			selector instanceof Window) {
			o = Object.assign(new Library, {0: selector});
			length = 1;
		} else {
			selectors = (context || document).querySelectorAll(selector);
			if (!selectors.length) return null;
			length = selectors.length;
			o = Object.assign(new Library, {...selectors});
		}

		Object.defineProperties(o, {
			'length': {value: length, enumerable: false},
			'selector': {value: selector, enumerable: false}
		});
		prevInstance = o;

		return o;
	}
}

window._ = factory();
})();