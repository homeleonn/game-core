<?php

namespace Homeleon\Captcha;

class Captcha
{
    public function __construct(private $session) {}

	public function set(){
		if(isset($_GET['async']))
			sleep(1);
		header('Content-Type: image/png'); // даем браузеру понять, что это картинка (png)

		$chars = array();
		$conside_registry = true; // учитывать регистр символов???

		// шрифты, можно подключать свои, $font_random = true - если символы разным шрифтом
		$fonts = array('','OpenSans-Bold.ttf','OpenSans-Bold.ttf'); // пути к шрифтам
		$font = $fonts[2];
		$font_random = false; // если true - каждый символ разным шрифтом из массива $fonts

		// размер шрифта
		$font_size = 40;

		// $symbols_random = true, если разное количество символов
		$symbols = 5; // количество символов
		$symbols_random = false;
		if ($symbols_random == true)
		{
			$symbols = mt_rand(1,10); // от 1 до 10 символов
		}

		/* раскомментируйте для фиксированной ширины и высоты
		$width = 150;
		$height = 40;
		по умолчанию - авто (см. ниже)
		*/

		$width = $font_size * $symbols + 50; // автоподстройка ширины и высоты рисунка (размер шрифта * количество символов + 25px для отступов слева и справа)
		$height = $font_size + 20; // вычисляется как размер шрифта + 10 px отступ сверху и снизу

		// случайный цвет символов - $text_color_random = true;
		$text_color_random = true;

		// угол начертания символов, $angle_random = true; - случайным образом
		$angle = 0;
		$angle_random = true;

		// алфавит - символы, генерирующиеся капчей
		$alphabet = array('a','A','b','B','c','C','d','D','e','E','f','F','g','G','h','H','i','I','j',
		'J','k','K','l','L','m','M','n','N','o','O','p','P','q','Q','r','R','s','S','t','T','u','U',
		'v','V','w','W','z','Z','Y','y','x','X','1','2','3','4','5','6','7','8','9','0');

		$img = imagecreate($width, $height);

		/* раскомментируйте, если градиент не нужен
		$bg = imagecolorallocate($img, 245, 245, 245); // цвет фона rgb
		if (true) // true - фон прозрачный
		{
		imageColorTransparent($img,$bg);
		}

		раскомментируйте, если градиент не нужен */

		/* фон градиентом - закомментируйте, если используете верхнее */
		$i = 0;
		$step = 1; // шаг градиента = 1
		$begin_color = array(190,190,190); // начальный цвет градиента(rgb) 190,190,190
		for ($i = 0; $i<3; $i++)
		{
			if (255 - $begin_color[$i] < $height + 2)
			{
			$begin_color[$i] = 253 - $height;
			}
		}
		do
		{
			$i++;
			$bg = imagecolorallocate($img, $begin_color[0]+$i*$step, $begin_color[1]+$i*$step, $begin_color[2]+$i*$step);
			imageLine($img,0,$i,$width+1,$i,$bg); // линейный вертикальный градиент
		}
		while ($i<$height+2);
		/* фон градиентом - закомментируйте, если используете верхнее */

		$r_color = imagecolorallocate($img, 0, 0, 0); // цвет рамки
		imageRectangle($img,1,1,$width-2,$height-2,$r_color);

		for ($i=0; $i<$symbols;$i++) // начинаем генерацию
		{
		if ($font_random == true) // случайный шрифт
		{
			$font = $fonts[mt_rand(1,count($fonts)-1)]; // если true - используем rand()
		}
		if ($angle_random == true) // проверяем нужен случайный угол начертания или нет
		{
			$angle = mt_rand(0,45); // если да рандомим
		}
		if ($text_color_random == true) // проверка нужен ли случайный цвет символов, аналогично
		{
			$text_color = "";
			$text_color = imagecolorallocate($img, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255));
		}
		else
		{
			$text_color = imagecolorallocate($img, 0, 0, 0); // если нет - черным цветом
		}
		$char = $alphabet[mt_rand(0,count($alphabet) - 1)]; // выбираем случаный символ из нашего алфавита
		if ($conside_registry == true) // проверяем, нужно ли учитывать регистр символов
		{
			$chars[] = $char;
		}
		else
		{
			$chars[] = strtolower($char); // если нет, переводим все символы в нижний регистр
		}
		imageTTFText($img, $font_size, $angle,$font_size*$i+30, ($height+$font_size)/2, $text_color, __DIR__ . '/' . $fonts[2], $char); // рисуем символ на холсте
		}

		$this->session->flash('captcha_code', implode("",$chars)); // передаем в сессию значение капчи для последующей проверки
		imagepng($img); exit;// создаем изображение (png)
	}

	public function isValid(){
		return
            isset($_POST['captcha_code']) &&
            $_POST['captcha_code'] != ''  &&
            strtoupper($this->session->get('captcha_code')) == strtoupper($_POST['captcha_code']);
	}
}
