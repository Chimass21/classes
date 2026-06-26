$source = Get-Location
$dest = Join-Path $source 'legacy-app'
if (-not (Test-Path $dest)) {
    New-Item -ItemType Directory -Path $dest | Out-Null
}
Get-ChildItem -Force | Where-Object { $_.Name -ne '.git' -and $_.Name -ne 'legacy-app' } | ForEach-Object {
    $target = Join-Path $dest $_.Name
    Copy-Item -Path $_.FullName -Destination $target -Recurse -Force
}
Write-Output 'Copy complete.'
