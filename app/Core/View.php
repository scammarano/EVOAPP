<?php
namespace App\Core;

class View
{
    private static $data = [];
    
    public static function set($key, $value)
    {
        self::$data[$key] = $value;
    }
    
    public static function get($key, $default = null)
    {
        return self::$data[$key] ?? $default;
    }
    
    public static function render($view, $layout = 'main')
    {
        $viewFile = __DIR__ . "/../Views/{$view}.php";
        $layoutFile = __DIR__ . "/../Views/layouts/{$layout}.php";
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: $viewFile");
        }
        
        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout file not found: $layoutFile");
        }
        
        // Extract data to variables
        extract(self::$data);
        
        // Make View helper functions available in views
        $viewHelper = new class {
            public function escape($string) {
                if ($string === null) {
                    return '';
                }

                if (is_bool($string)) {
                    return $string ? '1' : '0';
                }

                if (is_array($string) || is_object($string)) {
                    return '';
                }

                return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
            }
            
            public function asset($path) {
                return APP_URL . '/assets/' . ltrim($path, '/');
            }
            
            public function url($route) {
                return APP_URL . '/index.php?r=' . urlencode($route);
            }
            
            public function formatDate($date, $format = 'Y-m-d H:i:s') {
                if (!$date) return '';
                $timestamp = is_numeric($date) ? $date : strtotime($date);
                return date($format, $timestamp);
            }
            
            public function timeAgo($date) {
                if (!$date) return '';
                
                $timestamp = is_numeric($date) ? $date : strtotime($date);
                $diff = time() - $timestamp;
                
                if ($diff < 60) {
                    return 'ahora';
                } elseif ($diff < 3600) {
                    return floor($diff / 60) . ' min';
                } elseif ($diff < 86400) {
                    return floor($diff / 3600) . ' h';
                } elseif ($diff < 604800) {
                    return floor($diff / 86400) . ' días';
                } else {
                    return date('d/m/Y', $timestamp);
                }
            }
        };
        
        // Capture view content
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        // Include layout
        include $layoutFile;
    }
    
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    public static function asset($path)
    {
        return APP_URL . '/assets/' . ltrim($path, '/');
    }
    
    public static function url($route)
    {
        return APP_URL . '/index.php?r=' . urlencode($route);
    }
    
    public static function csrfField()
    {
        $token = Auth::generateCsrfToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
    }
    
    public static function flash($type, $message = null)
    {
        if ($message === null) {
            // Get flash message
            return $_SESSION['flash'][$type] ?? null;
        } else {
            // Set flash message
            $_SESSION['flash'][$type] = $message;
        }
    }
    
    public static function hasFlash($type)
    {
        return isset($_SESSION['flash'][$type]);
    }
    
    public static function clearFlash()
    {
        unset($_SESSION['flash']);
    }
    
    public static function formatDate($date, $format = 'Y-m-d H:i:s')
    {
        if (!$date) return '';
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
    
    public static function timeAgo($date)
    {
        if (!$date) return '';
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'ahora';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' min';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' h';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . ' días';
        } else {
            return self::formatDate($date, 'd/m/Y');
        }
    }
    
    public static function formatBytes($bytes)
    {
        if ($bytes === 0) return '0 Bytes';
        
        $units = ['Bytes', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
