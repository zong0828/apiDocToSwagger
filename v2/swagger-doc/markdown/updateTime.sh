#!/bin/sh

now=$(date +"%Y-%m-%d")
echo "$now"

date=$(cat /home/zong/data/swagger-doc/markdown/index.md | grep /api/backend_api.html | awk -F '|' '{print $3}')

echo $date

sed -i "s/$date/$now/g"  /home/zong/data/swagger-doc/markdown/index.md