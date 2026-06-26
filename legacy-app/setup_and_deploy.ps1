$repoPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $repoPath

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Error 'Git is not installed or not available in PATH. Install Git first and rerun this script.'
    exit 1
}

if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
    Write-Warning 'Node.js is not installed or not available in PATH. You only need Node for local development, not for pushing to GitHub.'
}

$remoteUrl = 'https://github.com/Chimass21/-Chimass21-BRAIN4.git'

Write-Output 'Initializing git repository...'
git init

git branch -M main

echo Adding files to git repository...
git add .

git commit -m 'Deploy project to GitHub' --allow-empty

$existingRemote = git remote get-url origin 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Output "Existing remote origin found: $existingRemote"
    if ($existingRemote -ne $remoteUrl) {
        Write-Output "Updating origin to $remoteUrl"
        git remote set-url origin $remoteUrl
    }
} else {
    Write-Output "Adding remote origin $remoteUrl"
    git remote add origin $remoteUrl
}

Write-Output 'Pushing to GitHub...'
git push -u origin main

if ($LASTEXITCODE -ne 0) {
    Write-Error 'Push failed. Check your GitHub credentials or remote repository access.'
    exit 1
}

Write-Output 'GitHub deployment completed successfully.'
