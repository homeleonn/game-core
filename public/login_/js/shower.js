function Shower(cl)
{
	if(typeof window.showerPluginLoad != "undefined") return false;
	window.showerPluginLoad = true;

	$('body').one('click', 'a' + cl, function(e){
		e.preventDefault();
		new Shower1(cl);
		$(this).click();
	});

	function Shower1(cl){
		$('body').append('<div id="shower"><div id="shower-tools"><div id="shower-prev"></div><div id="shower-next"></div></div><span></span><div id="img"><img src="" alt=""><div id="counter">0 / 0</div><div id="shower-title"></div><div id="close">x</div></div></div>');
		var cl = cl || '.shower';

		var $imgs = $('a' + cl);
		var $wrapper = $('#shower');
		var $imgWrap = $('#shower > #img');
		var $img = $imgWrap.children('img');
		var $title = $imgWrap.children('#shower-title');
		var self = this;
		var isChrome = !!window.chrome && (!!window.chrome.webstore || !!window.chrome.runtime);

		this.index = 0;

		$imgWrap.children('#counter').html((this.index+1)+' / '+$imgs.length);

		this.get = function(src){
			//var newSrc = $(img).attr('data-large-img') ? 'data-large-img' : 'src';
			//$img.attr({'src':$(img).attr(src)});
			var ww = $(window).width();
			var wh = $(window).height();

			var showImg = new Image();
			showImg.onload = function(){
				$img.attr({'src':src.href});
				// console.log($img[0].naturalWidth, $img[0].naturalHeight);
				if(!$wrapper.hasClass('block')){
					$wrapper.addClass('block');
					$wrapper.animate({'opacity': 1}, 500);
				}

				var nw = showImg.naturalWidth;
				var nh = showImg.naturalHeight;

				$img.animate({'width': 'auto', 'height': 'auto','max-height': wh - 100}, 50, function(){
					//$img.css({'max-height': wh - 100});
					// console.log($img.width(), $img.height());
					// var h = $img.height();
					// if(h != nh){
						// var newWidth = nw * (h / nh);
						// if(newWidth > ww){
							// $img.css('height', nw * (newWidth / nw));
						// }
						// console.log(newWidth, h, nh);
						// $img.css('width', newWidth);
					// }

					$imgWrap.css('visibility','visible');
					$imgWrap.animate({
						'margin-left': ((-$imgWrap.width()/2-20+(ww < 400 ? 18 : 0))+'px'),
						'margin-top': (($imgWrap.height() > wh ? 0 : 20)+'px')
					}, 50);

					$img.animate({'opacity': 1}, 300);
				});
			}
			showImg.src = src.href;
			$title.text(src.title);
			self.setCounter();
		}

		this.hide = function(){
			self.index = 0;
			$wrapper.animate({'opacity': 0}, 500, function(){
				$wrapper.removeClass('block');
				$imgWrap.css({'visibility':'hidden'});
				self.setCounter();
			})
		}

		this.getIndex = function(img1){
			var index = 0;
			$imgs.each(function(i, img){
				if($(img)[0] == $(img1)[0]){
					index = i;
					return false;
				}
			});

			return index;
		}

		this.setCounter = function(){
			$imgWrap.children('#counter').html((self.index+1)+' / '+$imgs.length);
		}

		$('#shower #close, #shower > span').click(function(){
			self.hide();
		});

		$('#shower-prev').click(function(){
			self.index--;
			if(self.index < 0){
				self.hide();
				return false;
			}else
				$img.animate({'opacity': 0}, 300, function(){self.get($imgs[self.index])});


			self.setCounter();
		});

		$('#shower-next, #shower img').click(function(){
			self.index++;
			if(self.index >= $imgs.length){
				self.index = 0;
				self.hide();
				return false;
			}else
				$img.animate({'opacity': 0}, 300, function(){self.get($imgs[self.index])});
		});

		$('body').on('click', 'a' + cl, function(e){
			e.preventDefault();
			self.index = self.getIndex(this);
			self.get(this);
		});
	}
}


$(() => {

	Shower('.shower');
})
