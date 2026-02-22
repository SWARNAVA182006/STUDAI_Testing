# StudAI Career Platform - Start Services Script
# Run this to start all required services for development

# Define colors
$GREEN = "Green"
$YELLOW = "Yellow"
$RED = "Red"
$CYAN = "Cyan"

function Write-ColorOutput {
    param([string]$Message, [string]$Color = "White")
    Write-Host $Message -ForegroundColor $Color
}

Write-ColorOutput "==========================================" $CYAN
Write-ColorOutput "StudAI Career Platform - Service Manager" $CYAN
Write-ColorOutput "==========================================" $CYAN
Write-Host ""

# Check if running in project directory
if (-not (Test-Path "artisan")) {
    Write-ColorOutput "Error: Please run this script from the project root directory" $RED
    exit 1
}

# Check prerequisites
Write-ColorOutput "Checking services..." $YELLOW

$services = @(
    @{Name="PHP"; Command="php"; Check="--version"}
    @{Name="Redis"; Command="redis-server"; Check="--version"}
    @{Name="MySQL"; Command="mysql"; Check="--version"}
)

foreach ($service in $services) {
    try {
        $null = & $service.Command $service.Check 2>&1
        Write-ColorOutput "✓ $($service.Name) is available" $GREEN
    } catch {
        Write-ColorOutput "✗ $($service.Name) is not available" $RED
    }
}

Write-Host ""

# Start services in separate windows
Write-ColorOutput "Starting services..." $YELLOW
Write-Host ""

# 1. Laravel Development Server
Write-ColorOutput "→ Starting Laravel server (http://localhost:8000)..." $CYAN
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; php artisan serve"
Start-Sleep -Seconds 2

# 2. Queue Worker
Write-ColorOutput "→ Starting Queue Worker..." $CYAN
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; php artisan queue:work --queue=critical,ai,jobs,emails,notifications,analytics,default"
Start-Sleep -Seconds 1

# 3. Laravel Horizon
Write-ColorOutput "→ Starting Horizon (http://localhost:8000/horizon)..." $CYAN
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; php artisan horizon"
Start-Sleep -Seconds 1

# 4. Laravel Reverb (WebSockets)
Write-ColorOutput "→ Starting Reverb (WebSockets)..." $CYAN
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; php artisan reverb:start"
Start-Sleep -Seconds 1

# 5. Laravel Scheduler (optional for development)
Write-ColorOutput "→ Starting Scheduler..." $CYAN
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD'; php artisan schedule:work"
Start-Sleep -Seconds 1

# 6. Redis (if not running as service)
$redisRunning = Get-Process redis-server -ErrorAction SilentlyContinue
if (-not $redisRunning) {
    Write-ColorOutput "→ Starting Redis..." $CYAN
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "redis-server"
    Start-Sleep -Seconds 2
} else {
    Write-ColorOutput "✓ Redis already running" $GREEN
}

# 7. Meilisearch (if installed)
$meilisearchRunning = Get-Process meilisearch -ErrorAction SilentlyContinue
if (-not $meilisearchRunning) {
    Write-ColorOutput "→ Starting Meilisearch..." $CYAN
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "meilisearch --master-key=masterKey"
    Start-Sleep -Seconds 2
} else {
    Write-ColorOutput "✓ Meilisearch already running" $GREEN
}

Write-Host ""
Write-ColorOutput "==========================================" $GREEN
Write-ColorOutput "All services started! 🚀" $GREEN
Write-ColorOutput "==========================================" $GREEN
Write-Host ""

Write-ColorOutput "Access Points:" $CYAN
Write-ColorOutput "  → Application:  http://localhost:8000" $YELLOW
Write-ColorOutput "  → Horizon:      http://localhost:8000/horizon" $YELLOW
Write-ColorOutput "  → API Docs:     http://localhost:8000/api/documentation" $YELLOW
Write-ColorOutput "  → Telescope:    http://localhost:8000/telescope" $YELLOW
Write-Host ""

Write-ColorOutput "Running Services:" $CYAN
Write-ColorOutput "  → Laravel Server (port 8000)" $YELLOW
Write-ColorOutput "  → Queue Worker" $YELLOW
Write-ColorOutput "  → Horizon Dashboard" $YELLOW
Write-ColorOutput "  → Reverb (WebSockets)" $YELLOW
Write-ColorOutput "  → Scheduler" $YELLOW
Write-ColorOutput "  → Redis (port 6379)" $YELLOW
Write-ColorOutput "  → Meilisearch (port 7700)" $YELLOW
Write-Host ""

Write-ColorOutput "To stop all services, close the terminal windows or press Ctrl+C in each." $YELLOW
Write-Host ""
Write-ColorOutput "Happy coding! 💻" $GREEN
