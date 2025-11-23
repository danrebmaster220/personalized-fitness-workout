<?php
/**
 * Admin Account Seeder
 * Run this script to create or reset the admin account
 * 
 * Usage:
 *   php seed_admin.php
 * 
 * This script is safe to run multiple times - it will update existing admin
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/Database.php';

// ============================================
// CONFIGURATION - Edit these values
// ============================================
$ADMIN_CONFIG = [
    'email' => 'admin@fitsync.com',
    'password' => 'Admin@12345',  // Change this to your desired password
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'role' => 'admin'
];

// ============================================
// Script Start
// ============================================

echo "╔════════════════════════════════════════╗\n";
echo "║     FitSync Admin Account Seeder       ║\n";
echo "╚════════════════════════════════════════╝\n\n";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check if admin already exists
    $stmt = $conn->prepare("SELECT User_ID, Email, Role, Is_Verified FROM user WHERE Email = :email");
    $stmt->execute([':email' => $ADMIN_CONFIG['email']]);
    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Hash the password
    $hashedPassword = password_hash($ADMIN_CONFIG['password'], PASSWORD_DEFAULT);
    
    if ($existingAdmin) {
        echo "⚠️  Admin account already exists!\n\n";
        echo "Existing admin details:\n";
        echo "  • User ID: " . $existingAdmin['User_ID'] . "\n";
        echo "  • Email: " . $existingAdmin['Email'] . "\n";
        echo "  • Role: " . $existingAdmin['Role'] . "\n";
        echo "  • Verified: " . ($existingAdmin['Is_Verified'] ? 'YES' : 'NO') . "\n\n";
        
        // Ask if user wants to update
        echo "Do you want to UPDATE this admin account? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($response) === 'yes' || strtolower($response) === 'y') {
            // Update existing admin
            $stmt = $conn->prepare("
                UPDATE user 
                SET Password = :password,
                    FirstName = :firstName,
                    LastName = :lastName,
                    Role = :role,
                    Is_Verified = 1,
                    Updated_At = NOW()
                WHERE Email = :email
            ");
            
            $result = $stmt->execute([
                ':password' => $hashedPassword,
                ':firstName' => $ADMIN_CONFIG['first_name'],
                ':lastName' => $ADMIN_CONFIG['last_name'],
                ':role' => $ADMIN_CONFIG['role'],
                ':email' => $ADMIN_CONFIG['email']
            ]);
            
            if ($result) {
                echo "\n✅ Admin account UPDATED successfully!\n\n";
            } else {
                echo "\n❌ Failed to update admin account.\n\n";
                exit(1);
            }
        } else {
            echo "\n⏭️  Skipping update. Admin account unchanged.\n\n";
            exit(0);
        }
    } else {
        // Create new admin account
        echo "Creating new admin account...\n\n";
        
        $stmt = $conn->prepare("
            INSERT INTO user (
                FirstName, 
                LastName, 
                Email, 
                Password, 
                Role, 
                Is_Verified,
                Age,
                Height,
                Weight,
                Gender,
                Fitness_Level,
                Activity_Level,
                Login_Method,
                Created_At
            ) VALUES (
                :firstName,
                :lastName,
                :email,
                :password,
                :role,
                1,
                NULL,
                NULL,
                NULL,
                'other',
                'advanced',
                'moderate',
                'email',
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            ':firstName' => $ADMIN_CONFIG['first_name'],
            ':lastName' => $ADMIN_CONFIG['last_name'],
            ':email' => $ADMIN_CONFIG['email'],
            ':password' => $hashedPassword,
            ':role' => $ADMIN_CONFIG['role']
        ]);
        
        if ($result) {
            echo "✅ Admin account CREATED successfully!\n\n";
        } else {
            echo "❌ Failed to create admin account.\n\n";
            exit(1);
        }
    }
    
    // Display final credentials
    echo "╔════════════════════════════════════════╗\n";
    echo "║         Admin Credentials              ║\n";
    echo "╠════════════════════════════════════════╣\n";
    echo "║ Email:    " . str_pad($ADMIN_CONFIG['email'], 27) . "║\n";
    echo "║ Password: " . str_pad($ADMIN_CONFIG['password'], 27) . "║\n";
    echo "║ Role:     " . str_pad($ADMIN_CONFIG['role'], 27) . "║\n";
    echo "╚════════════════════════════════════════╝\n\n";
    
    echo "⚠️  IMPORTANT SECURITY NOTES:\n";
    echo "  1. Change the password in this script before deployment\n";
    echo "  2. Never commit passwords to version control\n";
    echo "  3. Use strong passwords in production\n";
    echo "  4. Delete or secure this script after deployment\n\n";
    
    echo "✨ Done!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
