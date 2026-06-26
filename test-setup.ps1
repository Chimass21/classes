
# Test PHP and Composer setup
Write-Output "Testing PHP..."
$phpPath = (Get-ChildItem -Path "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\PHP.PHP.NTS.8.4_Microsoft.WinGet.Source_8wekyb3d8bbwe" -Recurse -Filter "php.exe").FullName
Write-Output "PHP found at: $phpPath"
&amp; $phpPath --version

Write-Output "`nTesting Composer..."
$composerPhar = Join-Path $env:LOCALAPPDATA "Composer\composer.phar"
if (Test-Path $composerPhar) {
    Write-Output "Composer found at: $composerPhar"
    &amp; $phpPath $composerPhar --version
} else {
    Write-Output "Composer not found at $composerPhar"
}

Write-Output "`nChecking composer.json..."
if (Test-Path "composer.json") {
    Write-Output "composer.json found"
    Get-Content composer.json
} else {
    Write-Output "composer.json not found"
}
