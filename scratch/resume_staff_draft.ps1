$ErrorActionPreference = 'Stop'

function Get-EnvValue([string] $Name) {
    $line = Get-Content -LiteralPath '.env' | Where-Object { $_ -match ('^' + [regex]::Escape($Name) + '=') } | Select-Object -First 1
    if (-not $line) { return '' }
    return ($line -split '=', 2)[1].Trim("`r", "`n")
}

$port = Get-EnvValue 'HTTP_PORT'; if (-not $port) { $port = '8080' }
$adminDir = Get-EnvValue 'ADMIN_DIR'; if (-not $adminDir) { $adminDir = 'admin-salisberg' }
$admin = "http://localhost:$port/$adminDir/"
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri ($admin + 'index.php?controller=AdminLogin') | Out-Null
$reply = Invoke-WebRequest -UseBasicParsing -WebSession $session -Method Post -Uri ($admin + 'index.php?controller=AdminLogin') -Body @{
    ajax = '1'; token = ''; controller = 'AdminLogin'; submitLogin = '1'
    email = 'staff.test@example.com'; passwd = 'FrontDesk-Test-Only-2026!'; redirect = 'AdminDashboard'
}
$login = $reply.Content | ConvertFrom-Json
$dashboard = Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri ($admin + $login.redirect)
$roomLink = [regex]::Match($dashboard.Content, 'index\.php\?controller=AdminSalisbergRoomBoard&amp;token=[a-f0-9]+')
$boardUrl = ([Uri]::new([Uri] $admin, [System.Net.WebUtility]::HtmlDecode($roomLink.Value))).AbsoluteUri
$board = Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri $boardUrl
$resume = [regex]::Match($board.Content, 'href="([^"]*controller=AdminSalisbergWalkIn[^"]*id_walkin_draft=5[^"]*)"')
if (-not $resume.Success) { throw 'Draft 5 resume link was not found.' }
$walkinUrl = ([Uri]::new([Uri] $admin, [System.Net.WebUtility]::HtmlDecode($resume.Groups[1].Value))).AbsoluteUri
$result = Invoke-WebRequest -UseBasicParsing -WebSession $session -Method Post -Uri $walkinUrl -Body @{
    sb_discard_draft = '1'; id_walkin_draft = '5'
}
if (-not $result.Content.Contains('was discarded and its room was released')) {
    throw 'The failed Hotel Staff draft could not be discarded.'
}
Write-Output 'ok: failed test draft discarded'
