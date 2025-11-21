<?php
// Simple health check that doesn't depend on the database
// This allows Render to verify the web server is running immediately
http_response_code(200);
echo "OK";
?>
