@echo off
set installer=%TEMP%\Git-2.42.0-64-bit.exe
if not exist "%installer%" (
  echo Git installer missing
  exit /b 1
)
echo Installer found: %installer%
dir "%installer%"
"%installer%" /VERYSILENT /NORESTART /SP- /COMPONENTS=icons,ext,assoc
echo Installer exit code: %ERRORLEVEL%
where git || echo git-not-found
