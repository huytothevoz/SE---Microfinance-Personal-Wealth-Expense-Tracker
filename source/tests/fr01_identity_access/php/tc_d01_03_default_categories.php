<?php

declare(strict_types=1);

require_once __DIR__ . '/base.php';

runAndSendJson(function () {
    $ctx = setupTestContext();
    $hash = password_hash('User@1234', PASSWORD_BCRYPT, ['cost' => 12]);
    $id = $ctx['userDao']->createUser('Category Test', 'cat.test@wealthflow.local', $hash, 'VND');
    $ctx['userDao']->copyDefaultCategories($id);

    $stmt = $ctx['pdo']->prepare('SELECT COUNT(*) as cnt FROM categories WHERE user_id = ?');
    $stmt->execute([$id]);
    $catCount = (int) $stmt->fetchColumn();
    $defaultCount = (int) $ctx['pdo']->query('SELECT COUNT(*) FROM default_categories WHERE is_active = 1')->fetchColumn();

    return [
        'user_id' => $id,
        'user_cat_count' => $catCount,
        'default_active_count' => $defaultCount,
        'matched' => $catCount > 0 && $catCount === $defaultCount,
    ];
});
