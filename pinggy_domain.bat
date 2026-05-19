@echo off
title Pinggy Engelsiz Domain
echo Siteniz internete aciliyor...
ssh -p 443 -R 0:localhost:5000 a.pinggy.io
pause