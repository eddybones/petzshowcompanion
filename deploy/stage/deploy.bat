@echo off

rmdir /s /q build 2>NUL

git clone -b master %PSC_REPO% build

pushd build

set APP_ENV=stage
set APP_DEBUG=1
call composer install --optimize-autoloader
echo. >> .env.stage
echo PSC_STAGE_MYSQL_USER=%PSC_STAGE_MYSQL_USER% >> .env.stage
echo PSC_STAGE_MYSQL_PASSWORD="%PSC_STAGE_MYSQL_PASSWORD%" >> .env.stage
call composer dump-env stage
call npm run build

rmdir /s /q assets
rmdir /s /q tests
rmdir /s /q .gitpsc
del .env
del .env.stage
del .env.prod
del .gitignore
del composer.json
move composer.deploy.json composer.json
del composer.lock
del package.json
del package-lock.json
del phpunit.xml.dist
del symfony.lock
del webpack.config.js
del pscbackup.sh
del psc-mailer.service

call "C:\Program Files\7-Zip\7z.exe" a -tzip stage_build.zip * -x!deploy.*

call "C:\Program Files (x86)\WinSCP\WinSCP.com" /ini=nul /script=..\deploy.winscp.txt /log=deploy.log /loglevel=0

popd

call putty.exe %CW_SERVER_USER%@%CW_SERVER_IP% -i "%CW_SERVER_PPK%" -m "deploy.putty.txt" -sessionlog build\puttysession.log

echo.
echo Done.
