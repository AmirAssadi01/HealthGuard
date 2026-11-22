<?php

// Create a one-time password reset token for an email address.
function CreatePasswordResetToken($conn, $email)
{
    // Email matching is not case-sensitive.
    $email = strtolower($email);

    // Find the account that owns this email address.
    $result = mysqli_query(
        $conn,
        "SELECT user_id, name, email
         FROM users
         WHERE LOWER(email)='$email'
         LIMIT 1"
    );
    if (!$result) {
        return null;
    }

    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        return null;
    }

    // Mark older unused reset links for this user as finished.
    $user_id = (int)$user['user_id'];

    mysqli_query(
        $conn,
        "UPDATE password_reset_tokens
         SET used_at=NOW()
         WHERE user_id='$user_id' AND used_at IS NULL"
    );

    // Create a random public token and save only its hash.
    $token = bin2hex(random_bytes(32));
    $token_hash = hash("sha256", $token);

    $saved = mysqli_query(
        $conn,
        "INSERT INTO password_reset_tokens (
             user_id,
             token_hash,
             expires_at
         )
         VALUES (
             '$user_id',
             '$token_hash',
             DATE_ADD(NOW(), INTERVAL 30 MINUTE)
         )"
    );

    if (!$saved) {
        return null;
    }

    // Return the saved reset information so the email can be built.
    $user['reset_id'] = mysqli_insert_id($conn);
    $user['token'] = $token;
    return $user;
}

// Check that a password reset token exists and has not expired.
function ValidatePasswordResetToken($conn, $token)
{
    // A valid token contains exactly 64 hexadecimal characters.
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    // Hash the public token before comparing it with the saved hash.
    $token_hash = hash("sha256", $token);
    $result = mysqli_query(
        $conn,
        "SELECT password_reset_tokens.reset_id,
                password_reset_tokens.user_id,
                users.name,
                users.email
         FROM password_reset_tokens
         JOIN users
             ON password_reset_tokens.user_id=users.user_id
         WHERE password_reset_tokens.token_hash='$token_hash'
         AND password_reset_tokens.used_at IS NULL
         AND password_reset_tokens.expires_at>NOW()
         LIMIT 1"
    );
    if (!$result) {
        return null;
    }

    // Return the matching reset row, or null when no row was found.
    $reset = mysqli_fetch_assoc($result);
    return $reset ?: null;
}

// Save the new password and finish all open reset links for this user.
function ConsumePasswordResetToken($conn, $reset_id, $user_id, $new_password)
{
    // Convert IDs to numbers before using them in the query.
    $reset_id = (int)$reset_id;
    $user_id = (int)$user_id;

    $result = mysqli_query(
        $conn,
        "SELECT reset_id
         FROM password_reset_tokens
         WHERE reset_id='$reset_id'
         AND user_id='$user_id'
         AND used_at IS NULL
         AND expires_at>NOW()"
    );
    if (!$result || mysqli_num_rows($result) == 0) {
        return false;
    }

    // Hash and save the new password.
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $updated = mysqli_query(
        $conn,
        "UPDATE users
         SET password='$password_hash'
         WHERE user_id='$user_id'"
    );

    if (!$updated) {
        return false;
    }

    // Mark every unused reset token for this user as used.
    $used = mysqli_query(
        $conn,
        "UPDATE password_reset_tokens
         SET used_at=NOW()
         WHERE user_id='$user_id' AND used_at IS NULL"
    );
    // Tell the reset page whether the final update worked.
    return (bool)$used;
}
