<?php

return [
    'user table' => 'orange_users',
    'role table' => 'orange_roles',
    'permission table' => 'orange_permissions',
    'user role table' => 'orange_user_role',
    'role permission table' => 'orange_role_permission',
    'user meta table' => 'orange_user_meta',
    'admin user' => 1,
    'guest user' => 2,
    'admin role' => 1,
    'everyone role' => 2,
    'sessionKey' => '##user##session##',
    'userModel' => \peels\acl\models\UserModel::class,
    'roleModel' => \peels\acl\models\RoleModel::class,
    'permissionModel' => \peels\acl\models\PermissionModel::class,
];
