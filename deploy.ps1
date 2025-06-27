# Research Apps - Windows PowerShell Deployment Script
# This script helps deploy the Research Apps application to a Windows server with IIS

param(
    [string]$SiteName = "ResearchApps",
    [string]$AppPath = "C:\inetpub\wwwroot\research_apps",
    [string]$DatabaseName = "research_apps_db",
    [string]$DatabaseUser = "research_user",
    [switch]$Help
)

# Display help information
if ($Help) {
    Write-Host "Research Apps Deployment Script for Windows" -ForegroundColor Green
    Write-Host ""
    Write-Host "Usage: .\deploy.ps1 [-SiteName <name>] [-AppPath <path>] [-DatabaseName <name>] [-DatabaseUser <user>]" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Parameters:"
    Write-Host "  -SiteName      Name of the IIS site (default: ResearchApps)"
    Write-Host "  -AppPath       Application deployment path (default: C:\inetpub\wwwroot\research_apps)"
    Write-Host "  -DatabaseName  Database name (default: research_apps_db)"
    Write-Host "  -DatabaseUser  Database username (default: research_user)"
    Write-Host "  -Help          Show this help message"
    Write-Host ""
    Write-Host "Prerequisites:"
    Write-Host "  - IIS with PHP support"
    Write-Host "  - MySQL Server"
    Write-Host "  - Administrator privileges"
    exit
}

# Function to write colored output
function Write-Status {
    param([string]$Message, [string]$Color = "Green")
    Write-Host "[INFO] $Message" -ForegroundColor $Color
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

# Function to check if running as administrator
function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# Function to check prerequisites
function Test-Prerequisites {
    Write-Status "Checking prerequisites..."
    
    # Check if running as administrator
    if (-not (Test-Administrator)) {
        Write-Error "This script must be run as Administrator"
        exit 1
    }
    
    # Check if IIS is installed
    $iisFeature = Get-WindowsFeature -Name IIS-WebServerRole -ErrorAction SilentlyContinue
    if (-not $iisFeature -or $iisFeature.InstallState -ne "Installed") {
        Write-Error "IIS is not installed. Please install IIS first."
        exit 1
    }
    
    # Check if PHP is available
    try {
        $phpVersion = & php -v 2>$null
        if ($LASTEXITCODE -ne 0) {
            throw "PHP not found"
        }
        Write-Status "PHP is installed"
    }
    catch {
        Write-Error "PHP is not installed or not in PATH. Please install PHP first."
        exit 1
    }
    
    # Check if MySQL is available
    try {
        $mysqlVersion = & mysql --version 2>$null
        if ($LASTEXITCODE -ne 0) {
            throw "MySQL not found"
        }
        Write-Status "MySQL is installed"
    }
    catch {
        Write-Error "MySQL is not installed or not in PATH. Please install MySQL first."
        exit 1
    }
    
    Write-Status "All prerequisites are met"
}

# Function to create backup
function New-Backup {
    if (Test-Path $AppPath) {
        Write-Status "Creating backup of existing installation..."
        $backupPath = "C:\Backups\ResearchApps\backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
        New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
        Copy-Item -Path $AppPath -Destination $backupPath -Recurse -Force
        Write-Status "Backup created at: $backupPath"
    }
}

# Function to setup database
function Set-Database {
    Write-Status "Setting up database..."
    
    # Get MySQL credentials
    $mysqlRootPassword = Read-Host "Enter MySQL root password" -AsSecureString
    $rootPassword = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($mysqlRootPassword))
    
    $dbPassword = Read-Host "Enter password for database user '$DatabaseUser'" -AsSecureString
    $userPassword = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPassword))
    
    # Create SQL commands
    $sqlCommands = @"
CREATE DATABASE IF NOT EXISTS $DatabaseName;
CREATE USER IF NOT EXISTS '$DatabaseUser'@'localhost' IDENTIFIED BY '$userPassword';
GRANT ALL PRIVILEGES ON $DatabaseName.* TO '$DatabaseUser'@'localhost';
FLUSH PRIVILEGES;
"@
    
    # Execute SQL commands
    try {
        $sqlCommands | & mysql -u root -p"$rootPassword" 2>$null
        if ($LASTEXITCODE -ne 0) {
            throw "MySQL command failed"
        }
        Write-Status "Database setup completed"
        
        # Store credentials for later use
        $global:DbConfig = @{
            Host = "localhost"
            Database = $DatabaseName
            Username = $DatabaseUser
            Password = $userPassword
        }
    }
    catch {
        Write-Error "Failed to setup database. Please check your MySQL credentials."
        exit 1
    }
}

# Function to deploy application files
function Copy-ApplicationFiles {
    Write-Status "Deploying application files..."
    
    # Create deployment directory
    if (-not (Test-Path $AppPath)) {
        New-Item -ItemType Directory -Path $AppPath -Force | Out-Null
    }
    
    # Copy files from current directory
    $sourceFiles = Get-ChildItem -Path "." -Exclude @("deploy.ps1", "deploy.sh", "*.md", ".git*")
    foreach ($file in $sourceFiles) {
        Copy-Item -Path $file.FullName -Destination $AppPath -Recurse -Force
    }
    
    Write-Status "Files deployed successfully to: $AppPath"
}

# Function to configure database connection
function Set-DatabaseConfig {
    Write-Status "Configuring database connection..."
    
    $databaseConfigPath = Join-Path $AppPath "config\database.php"
    
    $configContent = @"
<?php
class Database {
    private `$host = "$($global:DbConfig.Host)";
    private `$db_name = "$($global:DbConfig.Database)";
    private `$username = "$($global:DbConfig.Username)";
    private `$password = "$($global:DbConfig.Password)";
    public `$conn;

    public function getConnection() {
        `$this->conn = null;
        
        try {
            `$this->conn = new PDO(
                "mysql:host=" . `$this->host . ";dbname=" . `$this->db_name,
                `$this->username,
                `$this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException `$exception) {
            error_log("Connection error: " . `$exception->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
        
        return `$this->conn;
    }
}
?>
"@
    
    Set-Content -Path $databaseConfigPath -Value $configContent -Encoding UTF8
    Write-Status "Database configuration updated"
}

# Function to configure IIS
function Set-IISConfiguration {
    Write-Status "Configuring IIS..."
    
    # Import WebAdministration module
    Import-Module WebAdministration -ErrorAction SilentlyContinue
    
    # Create application pool
    $appPoolName = "$SiteName`_AppPool"
    if (Get-IISAppPool -Name $appPoolName -ErrorAction SilentlyContinue) {
        Remove-WebAppPool -Name $appPoolName
    }
    
    New-WebAppPool -Name $appPoolName -Force
    Set-ItemProperty -Path "IIS:\AppPools\$appPoolName" -Name "processModel.identityType" -Value "ApplicationPoolIdentity"
    
    # Create website
    if (Get-Website -Name $SiteName -ErrorAction SilentlyContinue) {
        Remove-Website -Name $SiteName
    }
    
    New-Website -Name $SiteName -Port 80 -PhysicalPath $AppPath -ApplicationPool $appPoolName
    
    # Configure default document
    Add-WebConfiguration -Filter "system.webServer/defaultDocument/files" -Value @{value="index.php"} -PSPath "IIS:\" -Location "$SiteName"
    
    Write-Status "IIS configuration completed"
    Write-Status "Website created: $SiteName"
    Write-Status "Application Pool: $appPoolName"
}

# Function to set file permissions
function Set-FilePermissions {
    Write-Status "Setting file permissions..."
    
    # Grant IIS_IUSRS read and execute permissions
    $acl = Get-Acl $AppPath
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule("IIS_IUSRS", "ReadAndExecute", "ContainerInherit,ObjectInherit", "None", "Allow")
    $acl.SetAccessRule($accessRule)
    Set-Acl -Path $AppPath -AclObject $acl
    
    Write-Status "File permissions set successfully"
}

# Function to cleanup development files
function Remove-DevelopmentFiles {
    Write-Status "Cleaning up development files..."
    
    $filesToRemove = @(
        "deploy.ps1",
        "deploy.sh",
        "DEPLOYMENT_GUIDE.md",
        "DEPLOYMENT_CHECKLIST.md",
        "config\database.prod.php"
    )
    
    foreach ($file in $filesToRemove) {
        $filePath = Join-Path $AppPath $file
        if (Test-Path $filePath) {
            Remove-Item $filePath -Force
        }
    }
    
    Write-Status "Cleanup completed"
}

# Function to test deployment
function Test-Deployment {
    Write-Status "Testing deployment..."
    
    # Test if website is accessible
    try {
        $response = Invoke-WebRequest -Uri "http://localhost/" -UseBasicParsing -TimeoutSec 10
        if ($response.StatusCode -eq 200) {
            Write-Status "Website is accessible"
        }
        else {
            Write-Warning "Website returned status code: $($response.StatusCode)"
        }
    }
    catch {
        Write-Warning "Could not test website accessibility: $($_.Exception.Message)"
    }
    
    Write-Status "Testing completed"
}

# Function to display final instructions
function Show-FinalInstructions {
    Write-Status "Deployment completed successfully!" "Green"
    Write-Host ""
    Write-Status "Next steps:" "Cyan"
    Write-Host "1. Visit http://localhost/setup_database.php to initialize the database"
    Write-Host "2. Test the application with default credentials:"
    Write-Host "   - Admin: admin / admin123"
    Write-Host "   - Mentor: mentor / mentor123"
    Write-Host "   - Councillor: councillor / councillor123"
    Write-Host "   - RBM: rbm / rbm123"
    Write-Host "3. Delete setup_database.php after successful initialization"
    Write-Host "4. Configure domain name in IIS if needed"
    Write-Host "5. Setup SSL certificate"
    Write-Host "6. Configure regular backups"
    Write-Host ""
    Write-Warning "Important security reminders:"
    Write-Host "- Change default passwords immediately"
    Write-Host "- Remove setup files after initialization"
    Write-Host "- Keep the system updated"
    Write-Host "- Monitor IIS logs regularly"
    Write-Host ""
    Write-Status "Application Path: $AppPath" "Cyan"
    Write-Status "IIS Site Name: $SiteName" "Cyan"
    Write-Status "Database Name: $DatabaseName" "Cyan"
}

# Main deployment function
function Start-Deployment {
    Write-Status "Starting Research Apps deployment for Windows..." "Green"
    Write-Host ""
    
    try {
        Test-Prerequisites
        New-Backup
        Set-Database
        Copy-ApplicationFiles
        Set-DatabaseConfig
        Set-IISConfiguration
        Set-FilePermissions
        Remove-DevelopmentFiles
        Test-Deployment
        Show-FinalInstructions
        
        Write-Status "Deployment script completed successfully!" "Green"
    }
    catch {
        Write-Error "Deployment failed: $($_.Exception.Message)"
        Write-Host "Please check the error above and try again."
        exit 1
    }
}

# Run main deployment function
Start-Deployment 