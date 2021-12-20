/*HOOKS*/
const hooks = {};

function addAction(actionName, cb) {
	if (!hooks[actionName]) hooks[actionName] = [];
	hooks[actionName].push(cb);
}

function doAction(actionName, ...args) {
	hooks[actionName]?.forEach(cb => cb(...args));
}
/*--HOOKS*/

function z(num) {
	return +num > 9 ? num : '0' + num;
}

function timer(seconds, format = 'H:i:s') {
	const delimiter = ':';
	const times = {};
	times.hours = seconds / 3600;
	seconds = seconds % 3600;
	times.minutes = seconds / 60;
	seconds = seconds % 60;
	times.seconds = seconds;


	const formats = {
		'H': 'hours',
		'i': 'minutes',
		's': 'seconds',
	}

	const result = format.split(delimiter).map(f => {
		return z(Math.floor(times[formats[f]]))
	}).join(delimiter)

	return result;
	// const time = [hours, minutes, seconds].map((item) => {
	// 	return z(Math.floor(item));
	// });
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

function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function toNums(obj) {
	for (var key in obj) {
		if (isNumeric(obj[key])) {
			obj[key] = +obj[key];
		}
	}

	return obj;
}

function getTimeSeconds() {
	return new Date().getTime() / 1000;
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
		"d": 'Date'
	};

	return format.replace(/([YymdHis])/g, item => {
		itemDate = date['get' + formats[item]]();
		if (item === 'm') {
			itemDate += 1;
		}
		let s = z(itemDate);
		return item === 'y' ? s.toString().substr(2, 2) : s;
	});
}

function setHpLineStyle(curHp, maxHp) {
	const curHpInPercent = (curHp / maxHp) * 100;
	const color =
		curHpInPercent < 33
			? "#993e3e"
			: curHpInPercent < 66
			? "#dddd42"
			: "green";

	return {
		width: (curHp / maxHp) * 100 + "%",
		backgroundColor: color
	}
}

function rand(min, max) {
	return Math.round(min - 0.5 + Math.random() * (max - min + 1)) + 0;
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

	parent() {
		return _(this[0].parentNode);
	}

	toArray(raw = null) {
		return Array.prototype.slice.call(raw ? raw : this);
	}

	css(key, value = null) {
		if (typeof key !== 'object') {
			if (!value) {
				let val = this[0].style[key];
				val = val ? val : (getComputedStyle(this[0])[key]);
				if (value === false) {
					val = +val.split('px')[0];
				}
				return val;
			}
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

	on(event, callback) {
		this.e(el => add(el, event, callback));
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
		// return this.attr(this[0])
	}

	each(callback, order = null) {
		for (let prop in this) {
			if (!isNaN(prop)) {
				callback(...(!order ? [+prop, this[prop]] : [this[prop], +prop]));
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
		// var event = new Event(e);
		this.e(element => {
			element[e]()
			// element.dispatchEvent(event);
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