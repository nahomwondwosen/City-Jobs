<?php
session_start();

// Remove all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to homepage or login page
header("Location: index.html");
exit;
