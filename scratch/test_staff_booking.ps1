$ErrorActionPreference = 'Stop'

function Get-EnvValue([string] $Name) {
    $line = Get-Content -LiteralPath '.env' | Where-Object { $_ -match ('^' + [regex]::Escape($Name) + '=') } | Select-Object -First 1
    if (-not $line) { return '' }
    return ($line -split '=', 2)[1].Trim("`r", "`n")
}

function Assert-Contains([string] $Text, [string] $Needle, [string] $Label) {
    if (-not $Text.Contains($Needle)) { throw "$Label did not contain: $Needle" }
    Write-Output "ok: $Label"
}

function Get-InputValue([string] $Html, [string] $Id) {
    $match = [regex]::Match($Html, 'id="' + [regex]::Escape($Id) + '"[^>]*value="([^"]*)"')
    if (-not $match.Success) { throw "Could not find input #$Id" }
    return [System.Net.WebUtility]::HtmlDecode($match.Groups[1].Value)
}

function Resolve-Link([string] $Base, [string] $Link) {
    $decoded = [System.Net.WebUtility]::HtmlDecode($Link)
    return ([Uri]::new([Uri] $Base, $decoded)).AbsoluteUri
}

$port = Get-EnvValue 'HTTP_PORT'
if (-not $port) { $port = '8080' }
$adminDir = Get-EnvValue 'ADMIN_DIR'
if (-not $adminDir) { $adminDir = 'admin-salisberg' }
$root = "http://localhost:$port"
$admin = "$root/$adminDir/"
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri ($admin + 'index.php?controller=AdminLogin') | Out-Null
$reply = Invoke-WebRequest -UseBasicParsing -WebSession $session -Method Post -Uri ($admin + 'index.php?controller=AdminLogin') -Body @{
    ajax = '1'; token = ''; controller = 'AdminLogin'; submitLogin = '1'
    email = 'staff.test@example.com'; passwd = 'FrontDesk-Test-Only-2026!'; redirect = 'AdminDashboard'
}
$login = $reply.Content | ConvertFrom-Json
if (-not $login.redirect) { throw 'Hotel Staff sign-in returned no redirect.' }
Write-Output 'ok: Hotel Staff signed in'

$dashboard = Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri ($admin + $login.redirect)
Assert-Contains $dashboard.Content 'Today at the desk' 'Hotel Staff dashboard'

$roomLink = [regex]::Match($dashboard.Content, 'index\.php\?controller=AdminSalisbergRoomBoard&amp;token=[a-f0-9]+')
if (-not $roomLink.Success) { throw 'Hotel Staff has no Room board menu link.' }
Write-Output 'ok: Hotel Staff has Room board access'
$boardUrl = Resolve-Link $admin $roomLink.Value
$initialBoard = Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri $boardUrl
$hotel = [regex]::Match($initialBoard.Content, 'name="id_hotel" value="([0-9]+)"')
if (-not $hotel.Success) { throw 'Hotel Staff Room board has no hotel.' }

$arrival = (Get-Date).Date.AddDays(120).ToString('yyyy-MM-dd')
$departure = (Get-Date).Date.AddDays(122).ToString('yyyy-MM-dd')
$datedBoardUrl = $boardUrl + '&arrival=' + $arrival + '&departure=' + $departure + '&nights=2&adults=1&children=0&id_hotel=' + $hotel.Groups[1].Value
$board = Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri $datedBoardUrl
Assert-Contains $board.Content 'Create reservation' 'Future booking action'
$roomId = Get-InputValue $board.Content 'sb-selected-room-id'
if (-not $roomId) { throw 'No available room was selected for the test stay.' }

$started = Invoke-WebRequest -UseBasicParsing -WebSession $session -Method Post -Uri $boardUrl -Body @{
    sb_start_booking = '1'; id_hotel = $hotel.Groups[1].Value; id_room = $roomId
    arrival = $arrival; departure = $departure; nights = '2'; adults = '1'; children = '0'
}
$walkinUrl = $started.BaseResponse.ResponseUri.AbsoluteUri
$draftMatch = [regex]::Match($walkinUrl, 'id_walkin_draft=([0-9]+)')
if (-not $draftMatch.Success) { throw 'Hotel Staff could not start the booking draft.' }
$draftId = $draftMatch.Groups[1].Value
Assert-Contains $started.Content 'Guest information' 'Guest step'

$phone = '2339' + (Get-Date -Format 'MMddHHmm')
$payment = Invoke-WebRequest -UseBasicParsing -WebSession $session -Method Post -Uri $walkinUrl -Body @{
    sb_save_guest = '1'; id_customer = '0'; firstname = 'Hotel'; lastname = 'Staff Test'
    phone = $phone; email = ''; company = ''; special_requests = 'Hotel Staff booking-flow test'
}
Assert-Contains $payment.Content 'What is the guest paying now?' 'Payment step'

$confirm = Invoke-WebRequest -UseBasicParsing -WebSession $session -Method Post -Uri $payment.BaseResponse.ResponseUri.AbsoluteUri -Body @{
    sb_save_payment = '1'; arrangement = 'later'; method = ''; amount = ''; tendered = ''; reference = ''
}
Assert-Contains $confirm.Content 'Confirm booking' 'Confirm step'
Assert-Contains $confirm.Content 'Confirm reservation' 'Future reservation confirmation'

$completed = Invoke-WebRequest -UseBasicParsing -WebSession $session -Method Post -Uri $confirm.BaseResponse.ResponseUri.AbsoluteUri -Body @{
    sb_finalize = 'reserve'; accept_price = '1'
}
Assert-Contains $completed.Content 'Reservation saved' 'Reservation success'
Assert-Contains $completed.Content 'Booking reference' 'Booking reference shown'
$orderLink = [regex]::Match($completed.Content, 'href="([^"]*controller=AdminOrders[^"]*id_order=([0-9]+)[^"]*)"')
if (-not $orderLink.Success) { throw 'The completed booking has no View stay link.' }
$orderId = $orderLink.Groups[2].Value
$orderUrl = Resolve-Link $admin $orderLink.Groups[1].Value
$order = Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri $orderUrl
if ($order.Content.Contains('You do not have permission')) { throw 'Hotel Staff created the booking but cannot view it.' }
Assert-Contains $order.Content 'Hotel Staff Test' 'Hotel Staff can view created stay'

$reference = [regex]::Match($completed.Content, 'Booking reference\s*<strong>([^<]+)</strong>').Groups[1].Value
$ordersLink = [regex]::Match($dashboard.Content, 'index\.php\?controller=AdminOrders&amp;token=[a-f0-9]+')
if (-not $ordersLink.Success) { throw 'Reservations menu link was not found.' }
$orders = Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri (Resolve-Link $admin $ordersLink.Value)
Assert-Contains $orders.Content $reference 'Reservation appears in list'

Write-Output "DRAFT_ID=$draftId"
Write-Output "ORDER_ID=$orderId"
Write-Output "REFERENCE=$reference"
Write-Output "ROOM_ID=$roomId"
Write-Output "ARRIVAL=$arrival"
Write-Output "DEPARTURE=$departure"
