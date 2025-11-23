<?php
/**
 * Database Cleanup Script for Deployment
 * 
 * This script removes test data and prepares database for production export
 * Run this BEFORE exporting your database for InfinityFree
 * 
 * Usage:
 *   php cleanup_database.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "╔════════════════════════════════════════╗\n";
echo "║   Database Cleanup for Deployment      ║\n";
echo "╚════════════════════════════════════════╝\n\n";

echo "⚠️  WARNING: This will delete test data!\n\n";
echo "This script will:\n";
echo "  • Remove test/unverified users (except admin)\n";
echo "  • Clear workout history\n";
echo "  • Clear API logs\n";
echo "  • Keep only production-ready data\n\n";

echo "Do you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$response = trim(fgets($handle));
fclose($handle);

if (strtolower($response) !== 'yes' && strtolower($response) !== 'y') {
    echo "\n⏭️  Cleanup cancelled.\n\n";
    exit(0);
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Starting cleanup...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Get current statistics
    $userCount = $conn->query("SELECT COUNT(*) FROM user")->fetchColumn();
    $workoutCount = $conn->query("SELECT COUNT(*) FROM generated_workout")->fetchColumn();
    $apiLogCount = $conn->query("SELECT COUNT(*) FROM api_logs")->fetchColumn();
    
    echo "Current database state:\n";
    echo "  • Users: $userCount\n";
    echo "  • Workouts: $workoutCount\n";
    echo "  • API Logs: $apiLogCount\n\n";
    
    // Step 1: Remove unverified test users (keep admin)
    echo "Step 1: Removing unverified test users...\n";
    $stmt = $conn->prepare("
        DELETE FROM user 
        WHERE Role != 'admin' 
        AND (
            Is_Verified = 0 
            OR Email LIKE '%test%' 
            OR Email LIKE '%@gmail.com' 
            OR Created_At < DATE_SUB(NOW(), INTERVAL 30 DAY)
        )
    ");
    $stmt->execute();
    $deletedUsers = $stmt->rowCount();
    echo "  ✅ Removed $deletedUsers test users\n\n";
    
    // Step 2: Clear workout history
    echo "Step 2: Clearing workout history...\n";
    $stmt = $conn->query("TRUNCATE TABLE generated_workout");
    echo "  ✅ Removed $workoutCount workout records\n\n";
    
    // Step 3: Clear API logs
    echo "Step 3: Clearing API logs...\n";
    $stmt = $conn->query("TRUNCATE TABLE api_logs");
    echo "  ✅ Removed $apiLogCount API log entries\n\n";
    
    // Step 4: Clear old verification/reset tokens
    echo "Step 4: Clearing expired tokens...\n";
    $stmt = $conn->prepare("
        UPDATE user 
        SET Verification_Token = NULL,
            Reset_Token = NULL,
            Reset_Expires = NULL
        WHERE Is_Verified = 1
    ");
    $stmt->execute();
    $clearedTokens = $stmt->rowCount();
    echo "  ✅ Cleared $clearedTokens expired tokens\n\n";
    
    // Step 5: Clear profile images (optional)
    echo "Step 5: Profile images...\n";
    $profileDir = __DIR__ . '/../uploads/profiles/';
    if (is_dir($profileDir)) {
        $files = glob($profileDir . '*');
        $fileCount = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $fileCount++;
            }
        }
        echo "  ✅ Removed $fileCount profile images\n\n";
    } else {
        echo "  ℹ️  Profile images directory not found\n\n";
    }
    
    // Final statistics
    $remainingUsers = $conn->query("SELECT COUNT(*) FROM user")->fetchColumn();
    $remainingWorkouts = $conn->query("SELECT COUNT(*) FROM generated_workout")->fetchColumn();
    $remainingLogs = $conn->query("SELECT COUNT(*) FROM api_logs")->fetchColumn();
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Cleanup Summary\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "Removed:\n";
    echo "  • Test Users: $deletedUsers\n";
    echo "  • Workouts: $workoutCount\n";
    echo "  • API Logs: $apiLogCount\n\n";
    
    echo "Remaining in database:\n";
    echo "  • Users: $remainingUsers (should be 1-2 admins)\n";
    echo "  • Workouts: $remainingWorkouts (should be 0)\n";
    echo "  • API Logs: $remainingLogs (should be 0)\n\n";
    
    // List remaining users
    echo "Remaining users:\n";
    $stmt = $conn->query("SELECT User_ID, Email, Role, Is_Verified FROM user");
    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "  • [%s] %s (%s)\n",
            $user['Role'],
            $user['Email'],
            $user['Is_Verified'] ? 'Verified' : 'Unverified'
        );
    }
    
    echo "\n╔════════════════════════════════════════╗\n";
    echo "║     Database Ready for Export! ✨      ║\n";
    echo "╚════════════════════════════════════════╝\n\n";
    
    echo "Next Steps:\n";
    echo "1. Export database via phpMyAdmin\n";
    echo "2. Verify admin account works (run seed_admin.php if needed)\n";
    echo "3. Upload to InfinityFree\n";
    echo "4. Import database on InfinityFree\n";
    echo "5. Run setup_database.php on production\n\n";
    
    echo "✨ Done!\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
