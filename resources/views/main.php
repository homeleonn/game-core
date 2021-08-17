<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/fontello.css">
	<link rel="stylesheet/less" href="css/wrapper.less">
	<link rel="stylesheet/less" href="css/main.less">
	<title>Fightworld</title>
</head>
<body>
	<header>
		<div class="avatar" style="background-image:url('img/images/0.png');" title="Персонаж"></div>
		<div class="info-wrapper">
			<div class="info">
				<div class="align"><img src="img/aligns/99.gif"></div>
				<div class="clan"><img src="img/clans/developers.png"></div>
				<div class="login"><?=s('name')?>[0]</div>
			</div>	
			<div class="hp-wrapper">
				<div class="hp-back">
					<div class="hp-line"></div>
				</div>
				<div class="hp"></div>
			</div>
		</div>
		<div class="top-panel flex">
			<div><img src="img/other/pack.jpg" title="Рюкзак"></div>
			<div><img src="img/other/location.jpg" title="Локация"></div>
			<div><img src="img/other/fight.jpg" title="Бои"></div>
			<div><img src="img/other/quest.jpg" title="Квесты"></div>
			<div><img src="img/other/info.jpg" title="Анкета"></div>
		</div>
	</header>
	<div id="location-wrapper">
		<div class="location">
			<div class="name"></div>
			<div class="svg-wrapper"><svg></svg></div>
			<img src="" class="map">
		</div>

		<div class="location-select">
			<div class="location-block locations">
				<div class="title">Локации</div>
				<div class="list"></div>
			</div>

			<div class="location-block objects">
				<div class="title">Объекты</div>
				<div class="list"></div>
			</div>

			<div class="location-block characters">
				<div class="title">Персонажи</div>
				<div class="list"></div>
			</div>

			<div class="location-pass">
				<div class="title"></div>
				<div class="progressbar">
					<div class="progress"></div>
				</div>
			</div>
		</div>
		
		<span class="loader"></span>
	</div>
	<footer>
		<div class="resizer">
			<div class="up">▲</div>
			<div class="down">▼</div>
		</div>
		<div id="chat">
			<div class="flex">
				<div id="messages"></div>
				<div id="chat-room">
					<div id="location-caption">
						<span class="name">...</span>
						(<span class="count">...</span>)
						<!-- <span class="update icon-spin3" title="Обновить"></span> -->
					</div>
					<div id="room-users"></div>
				</div>
			</div>
			<div id="bottom-panel">
				<div id="time"></div>
				<form id="sendmessage-form" class="flex">
					<div id="message-wrap"><input class="message" id="message" type="text" name="message" size="80" autocomplete="off"></div>
					<div><input type="image" src="img/chat/message_send.png" title="Отправить" id="sendmessage"></div>
				</form>
				<div><img src="img/chat/chat_clear.gif" title="очистить чат" id="chat-clear"></div>
				<div><img src="img/chat/chat.ico" title="Смайлы"></div>
				<form action="/logout" method="post" id="logout"><button name="logout">Выход</button></form>
			</div>
		</div>
	</footer>
	<script src="js/less.min.js"></script>
	<script src="//code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src='js/common.js'></script>
	<script src="js/WSocket.js"></script>
	<script src="js/Application.js"></script>
	<script src="js/User.js"></script>
	<script>
		let user = {
			name: '<?=s('name')?>',
			room: <?=s('room') ?: "''"?>,
			transitionTimeout: <?=s('transitionTimeout') ?: "''"?>,
		};
	</script>
	<script src="js/app.js"></script>
</body>
</html>