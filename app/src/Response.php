<?php
class Response {
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function error($code, $message, $statusCode = 400) {
        self::json(['error' => ['code' => $code, 'message' => $message]], $statusCode);
    }
}
