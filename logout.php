<?php

// Open the current session so its saved values can be removed.
session_start();

// Remove every value saved inside the session.
session_unset();

// End the session completely.
session_destroy();

// Return the visitor to the login page.
header("Location: /HealthGuard/login.php");

// Stop running this file after the redirect.
exit();
?>
