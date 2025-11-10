<?php
require '../config/email.php';

if (sendAppEmail("alshaik7813@gmail.com", "verification", ["token" => "TEST123456"])) {
    echo "✅ Test email sent!";
} else {
    echo "❌ Failed to send email.";
}
