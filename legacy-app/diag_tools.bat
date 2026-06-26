@echo off
if exist "C:\Program Files\Git\cmd\git.exe" echo GIT_CMD=FOUND
if exist "C:\Program Files\Git\bin\git.exe" echo GIT_BIN=FOUND
if exist "C:\Program Files\nodejs\node.exe" echo NODE=FOUND
if exist "C:\Program Files (x86)\nodejs\node.exe" echo NODE86=FOUND
where git || echo GIT_NOT_IN_PATH
where node || echo NODE_NOT_IN_PATH
where npm || echo NPM_NOT_IN_PATH
