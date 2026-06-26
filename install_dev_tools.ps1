$gitUrl = 'https://github.com/git-for-windows/git/releases/download/v2.42.0.windows.1/Git-2.42.0-64-bit.exe'
$nodeUrl = 'https://registry.npmmirror.com/-/binary/node/v22.20.0/node-v22.20.0-x64.msi'
$temp = $env:TEMP
$gitInstaller = Join-Path $temp 'Git-2.42.0-64-bit.exe'
$nodeInstaller = Join-Path $temp 'node-v22.20.0-x64.msi'

Write-Output "Downloading Git from $gitUrl"
Invoke-WebRequest -Uri $gitUrl -OutFile $gitInstaller -UseBasicParsing

Write-Output "Downloading Node from $nodeUrl"
Invoke-WebRequest -Uri $nodeUrl -OutFile $nodeInstaller -UseBasicParsing

Write-Output 'Installing Git...'
Start-Process -FilePath $gitInstaller -ArgumentList '/VERYSILENT','/NORESTART','/SP-','/COMPONENTS=icons,ext,assoc' -Wait

Write-Output 'Installing Node...'
Start-Process -FilePath 'msiexec.exe' -ArgumentList '/i', $nodeInstaller, '/quiet', '/norestart' -Wait

Write-Output 'INSTALL_COMPLETE'
