<?php
namespace App\Core;

/**
 * Authentication & authorisation. Backs sessions for the admin panel and
 * bearer tokens for the REST API. Permissions are resolved from the user's
 * role and cached on the session.
 */
class Auth
{
    private static ?array $user = null;

    /** Attempt a username/password login; returns the user row or null. */
    public static function attempt(string $email, string $password): ?array
    {
        $db = App::db();
        $user = $db->first(
            'SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1',
            [$email]
        );
        if (!$user || !Security::verify($password, $user['password'])) {
            return null;
        }
        return $user;
    }

    /** Establish an authenticated session for a user row. */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('hotel_id', $user['hotel_id']);
        self::$user = $user;

        App::db()->update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
    }

    public static function logout(): void
    {
        self::$user = null;
        Session::destroy();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** The current authenticated user (from session or API token). */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = Session::get('user_id');
        if ($id) {
            self::$user = App::db()->first('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function hotelId(): ?int
    {
        $u = self::user();
        return $u && $u['hotel_id'] ? (int) $u['hotel_id'] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        if (!$u) {
            return null;
        }
        $role = App::db()->first('SELECT slug FROM roles WHERE id = ?', [$u['role_id']]);
        return $role['slug'] ?? null;
    }

    /** Does the current user hold a permission? Super Admin bypasses checks. */
    public static function can(string $permission): bool
    {
        $u = self::user();
        if (!$u) {
            return false;
        }
        if (self::role() === 'super_admin') {
            return true;
        }
        $perms = self::permissions();
        return in_array($permission, $perms, true) || in_array('*', $perms, true);
    }

    /** Flat list of the user's permission slugs (cached per request). */
    public static function permissions(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $u = self::user();
        if (!$u) {
            return $cache = [];
        }
        $rows = App::db()->all(
            'SELECT p.slug FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?',
            [$u['role_id']]
        );
        return $cache = array_column($rows, 'slug');
    }

    /** Resolve a user from an API token (Authorization: Bearer ...). */
    public static function fromApiToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $row = App::db()->first(
            'SELECT u.* FROM api_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND (t.expires_at IS NULL OR t.expires_at > NOW())
             AND u.status = "active" LIMIT 1',
            [$hash]
        );
        if ($row) {
            self::$user = $row;
            App::db()->update('api_tokens', ['last_used_at' => date('Y-m-d H:i:s')], 'token_hash = :h', ['h' => $hash]);
        }
        return $row;
    }
}
