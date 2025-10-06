<?php
// auth.php
require_once 'config.php';

function login($username, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, password, is_admin, points FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && verifyPassword($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['points'] = $user['points'];
        
        // 更新最后登录时间
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        return true;
    }
    return false;
}

function register($username, $email, $password) {
    $db = getDB();
    
    // 检查用户名和邮箱是否已存在
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return false;
    }
    
    $hashedPassword = hashPassword($password);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, points) VALUES (?, ?, ?, 100)");
    return $stmt->execute([$username, $email, $hashedPassword]);
}
?>