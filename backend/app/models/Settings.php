<?php
require_once __DIR__ . '/../core/Database.php';

class Settings {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }

    // Get all settings, mask secrets by default
    public function getAll($maskSecrets = true) {
        $sql = "SELECT id, k, v, type, is_secret, description, autoload, created_at, updated_at FROM settings ORDER BY k ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($maskSecrets) {
            foreach ($rows as &$r) {
                if ((int)$r['is_secret'] === 1) {
                    $r['v'] = null;
                    $r['masked'] = true;
                } else {
                    if ($r['type'] === 'json' && $r['v'] !== null) {
                        $decoded = json_decode($r['v'], true);
                        if (json_last_error() === JSON_ERROR_NONE) $r['v'] = $decoded;
                    }
                }
            }
            unset($r);
        } else {
            foreach ($rows as &$r) {
                if ($r['type'] === 'json' && $r['v'] !== null) {
                    $decoded = json_decode($r['v'], true);
                    if (json_last_error() === JSON_ERROR_NONE) $r['v'] = $decoded;
                }
            }
            unset($r);
        }
        return $rows;
    }

    public function getAutoloadPublic() {
        $sql = "SELECT k, v, type FROM settings WHERE autoload = 1 AND is_secret = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $val = $r['v'];
            if ($r['type'] === 'json' && $val !== null) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE) $val = $decoded;
            }
            $out[$r['k']] = $val;
        }
        return $out;
    }

    public function getByKey($key, $maskSecret = true) {
        $sql = "SELECT id, k, v, type, is_secret, description, autoload, created_at, updated_at FROM settings WHERE k = :k LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['k' => $key]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) return null;
        if ((int)$r['is_secret'] === 1 && $maskSecret) {
            $r['v'] = null;
            $r['masked'] = true;
            return $r;
        }
        if ($r['type'] === 'json' && $r['v'] !== null) {
            $decoded = json_decode($r['v'], true);
            if (json_last_error() === JSON_ERROR_NONE) $r['v'] = $decoded;
        }
        return $r;
    }

    // Return settings history. If $key provided, filter to that setting key.
    public function getHistory($key = null, $limit = 100) {
        if ($key) {
            $sql = "SELECT h.id, s.k as setting_key, h.changed_by, h.old_value, h.new_value, h.reason, h.created_at
                    FROM settings_history h
                    LEFT JOIN settings s ON s.id = h.setting_id
                    WHERE s.k = :k
                    ORDER BY h.created_at DESC
                    LIMIT :lim";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':k', $key);
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $sql = "SELECT h.id, s.k as setting_key, h.changed_by, h.old_value, h.new_value, h.reason, h.created_at
                    FROM settings_history h
                    LEFT JOIN settings s ON s.id = h.setting_id
                    ORDER BY h.created_at DESC
                    LIMIT :lim";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    // Set or update a setting by key. $value may be scalar or array (if json)
    public function set($key, $value, $type = 'string', $changedBy = null, $reason = null) {
        $storeVal = null;
        if ($type === 'json' && (is_array($value) || is_object($value))) {
            $storeVal = json_encode($value);
        } elseif ($value === null) {
            $storeVal = null;
        } else {
            $storeVal = (string)$value;
        }

        $sql = "SELECT id, v FROM settings WHERE k = :k LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['k' => $key]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        try {
            if ($existing) {
                $this->db->beginTransaction();
                $histSql = "INSERT INTO settings_history (setting_id, changed_by, old_value, new_value, reason) VALUES (:sid, :uid, :oldv, :newv, :reason)";
                $hst = $this->db->prepare($histSql);
                $hst->execute([
                    'sid' => $existing['id'],
                    'uid' => $changedBy,
                    'oldv' => $existing['v'],
                    'newv' => $storeVal,
                    'reason' => $reason
                ]);

                $upd = $this->db->prepare("UPDATE settings SET v = :v, type = :type, updated_at = NOW() WHERE id = :id");
                $upd->execute(['v' => $storeVal, 'type' => $type, 'id' => $existing['id']]);
                $this->db->commit();
                return true;
            } else {
                $ins = $this->db->prepare("INSERT INTO settings (k, v, type, is_secret, description, autoload, created_at) VALUES (:k, :v, :type, 0, NULL, 1, NOW())");
                $ins->execute(['k' => $key, 'v' => $storeVal, 'type' => $type]);
                return true;
            }
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Settings::set error: " . $e->getMessage());
            return false;
        }
    }
}

?>
