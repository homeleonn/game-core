
setServerWorkload(60, 'Genesis');
function setServerWorkload(percent, serverName) {
	const progressBarClass = '.progressbar-circle2-wrapper';
	$(`${progressBarClass} .serverName`).html(`<div>Сервер ${serverName}</div><div class="server-workload"></div>`);
	const $circles = $(`${progressBarClass}  circle:not(.back)`);
	const fullLength = $circles[0].getTotalLength();
	const lengthPercent = fullLength * (percent / 100) - (fullLength * (10 / 360));

	$circles.each((i, item) => {
		item.style.strokeDasharray = lengthPercent + ' 1000';
	});
	counter('.server-workload', 60, 3, 1);
}



function counter(selector, num, time, miltiplier = 2) {
	const offsetDelay 	= 200;
	const delay = (time * 1000 - offsetDelay) / num * miltiplier;
	const $el 			= $(selector);
	let buffNum 		= 0;
	let timer;

	const f = function() {
		$el.text(Math.round(buffNum) + '%');
		buffNum >= num ? clearTimeout(timer) : timer = setTimeout(f, delay);
		buffNum += miltiplier;
	};f();
}

$('.inactive input').each(function(i, item) {
    if (['email', 'password', 'remember_me'].includes(item.name)) {
      $(item).attr('disabled', 'disabled');
    }
});

let entryClick = true;
$('.entry-button').on('click', (e) => {
  e.preventDefault();
  if (!entryClick) {
    entryClick = true;
    $('.captcha-image').attr('src', '/captcha');
    $('.captcha').toggleClass('none');
    // $('.captcha-image').attr('src', '/captcha').toggleClass('none');
  } else {
    $('form#login').trigger('submit');
  }
})
