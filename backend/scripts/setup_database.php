<?php
/**
 * Database Setup Script for Fresh Installation
 * 
 * This script sets up a fresh FitSync database with:
 * - All required tables
 * - Admin account
 * - Default settings
 * 
 * Usage:
 *   php setup_database.php
 * 
 * Run this on InfinityFree after uploading your files
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "╔════════════════════════════════════════╗\n";
echo "║   FitSync Database Setup Wizard        ║\n";
echo "╚════════════════════════════════════════╝\n\n";

// Configuration
$ADMIN_EMAIL = 'admin@fitsync.com';
$ADMIN_PASSWORD = 'Admin@12345'; // CHANGE THIS!
$ADMIN_FIRST_NAME = 'System';
$ADMIN_LAST_NAME = 'Administrator';

echo "⚠️  WARNING: This will set up a fresh database!\n";
echo "This script will create:\n";
echo "  • Admin account ($ADMIN_EMAIL)\n";
echo "  • Default system settings\n\n";

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "✅ Database connection successful!\n";
    echo "Database: " . $conn->query('SELECT DATABASE()')->fetchColumn() . "\n\n";
    
    // Step 1: Check/Create Admin Account
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Step 1: Setting up Admin Account\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $stmt = $conn->prepare("SELECT User_ID FROM user WHERE Email = :email");
    $stmt->execute([':email' => $ADMIN_EMAIL]);
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        echo "ℹ️  Admin account already exists (updating password)...\n";
        $hashedPassword = password_hash($ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE user 
            SET Password = :password,
                FirstName = :firstName,
                LastName = :lastName,
                Role = 'admin',
                Is_Verified = 1
            WHERE Email = :email
        ");
        $stmt->execute([
            ':password' => $hashedPassword,
            ':firstName' => $ADMIN_FIRST_NAME,
            ':lastName' => $ADMIN_LAST_NAME,
            ':email' => $ADMIN_EMAIL
        ]);
        echo "✅ Admin account updated\n\n";
    } else {
        echo "Creating admin account...\n";
        $hashedPassword = password_hash($ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO user (
                FirstName, LastName, Email, Password, Role, 
                Is_Verified, Gender, Fitness_Level, Activity_Level, Login_Method
            ) VALUES (
                :firstName, :lastName, :email, :password, 'admin',
                1, 'other', 'advanced', 'moderate', 'email'
            )
        ");
        $stmt->execute([
            ':firstName' => $ADMIN_FIRST_NAME,
            ':lastName' => $ADMIN_LAST_NAME,
            ':email' => $ADMIN_EMAIL,
            ':password' => $hashedPassword
        ]);
        echo "✅ Admin account created\n\n";
    }
    
    // Step 2: Setup Default Settings (if settings table exists)
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Step 2: Checking System Settings\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM settings");
        $settingsCount = $stmt->fetchColumn();
        
        if ($settingsCount == 0) {
            echo "Creating default settings...\n";
            $stmt = $conn->prepare("
                INSERT INTO settings (
                    app_name, 
                    home_description,
                    ai_provider,
                    updated_at
                ) VALUES (
                    'FitSync',
                    'Stay fit. Stay focused. Stay strong.',
                    'openai',
                    NOW()
                )
            ");
            $stmt->execute();
            echo "✅ Default settings created\n\n";
        } else {
            echo "✅ Settings already configured\n\n";
        }
    } catch (PDOException $e) {
        echo "ℹ️  Settings table not found (skipping)\n\n";
    }
    
    // Step 3: Display Summary
    echo "╔════════════════════════════════════════╗\n";
    echo "║         Setup Complete! ✨              ║\n";
    echo "╚════════════════════════════════════════╝\n\n";
    
    echo "Admin Login Credentials:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Email:    $ADMIN_EMAIL\n";
    echo "Password: $ADMIN_PASSWORD\n";
    echo "URL:      " . getenv('FRONTEND_URL') . "/login\n\n";
    
    echo "⚠️  IMPORTANT:\n";
    echo "1. Change the admin password immediately after first login\n";
    echo "2. Delete this script after deployment for security\n";
    echo "3. Keep your credentials secure\n\n";
    
    // Database Statistics
    echo "Database Statistics:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $tables = ['user', 'generated_workout', 'settings', 'api_logs'];
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo sprintf("%-20s : %d records\n", ucfirst($table), $count);
        } catch (PDOException $e) {
            echo sprintf("%-20s : Table not found\n", ucfirst($table));
        }
    }
    
    echo "\n✨ Your FitSync installation is ready!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration.\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
