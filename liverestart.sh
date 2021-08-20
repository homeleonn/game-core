#!/bin/bash

SRC_DIR=$(pwd)/
PREV_MODIFY=0
PREV_MODIFY_TEMP=0
PREV_MODIFY_DELTA=200

inotifywait -e modify --format '%w %f' -m -r $SRC_DIR |\
# inotifywait -e modify -m -r $SRC_DIR |\
(
while read
do
    # Получаем имя директории
    DIR=$(echo $REPLY | cut -f 1 -d' ')
    # Получаем имя файла
    FILE=$(echo $REPLY | cut -f 2 -d' ')

    # echo $DIR$FILE;
    # Передаем имена директории и файла в функцию
    # make_action $DIR $FILE
   let "PREV_MODIFY_TEMP = $PREV_MODIFY + $PREV_MODIFY_DELTA"
   if [ $PREV_MODIFY_TEMP -lt $(($(date +'%s%N')/1000000)) ]
   then
    PREV_MODIFY=$(($(date +'%s%N')/1000000))
    # echo 1;
    php restart.php $DIR
   fi
done
)
