
include ./docker/.env

server = php_$(APP_NAME)
db = mysql_$(APP_NAME)
nginx = mysql_$(APP_NAME)
redis = mysql_$(APP_NAME)


start:
	@docker-compose --env-file ./docker/.env up -d --remove-orphans
	@make socket_start

stop:
	@make socket_stop
	@docker-compose --env-file ./docker/.env stop

restart:
	@make stop
	@make start

start-browse:
	@make start
	@firefox --new-tab $(HOST):$(PORT)

connect_php:
	@docker exec -it $(server) bash

connect_db:
	@docker exec -it $(db) bash

connect_nginx:
	@docker exec -it $(nginx) bash

connect_redis:
	@docker exec -it $(redis) bash

socket_start:
	@sleep 1
	@docker exec -d $(server) /bin/sh -c "php app.php >> /var/www/$(APP_NAME).loc/storage/logs/server.log"

socket_stop:
	@echo "Socket server is shutdowning. Wait 1 second"
	@docker exec -d $(server) /bin/bash -c "php app.php -q"
	@sleep 1
