<?php
// Simple health check - just verify PHP is running
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'timestamp' => time()]);
