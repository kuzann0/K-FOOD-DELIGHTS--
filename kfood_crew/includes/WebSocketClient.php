<?php
class WebSocketClient {
    private $host = 'localhost';
    private $port = 8080;
    private $socket;

    public function __construct($host = null, $port = null) {
        if ($host) $this->host = $host;
        if ($port) $this->port = $port;
    }

    public function send($data) {
        try {
            // Create WebSocket connection
            $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 2);
            
            if (!$this->socket) {
                throw new Exception("Failed to connect to WebSocket server: $errstr ($errno)");
            }

            // Set socket to non-blocking
            stream_set_blocking($this->socket, false);

            // Prepare data
            $message = json_encode($data);
            
            // Send data
            $headers = $this->generateHeaders($message);
            fwrite($this->socket, $headers . $message);

            // Read response (non-blocking)
            $response = '';
            $startTime = time();
            
            while (time() - $startTime < 2) { // 2 second timeout
                $buffer = fread($this->socket, 8192);
                if ($buffer === false) {
                    break;
                }
                $response .= $buffer;
                
                // Check if we have a complete response
                if (strpos($response, "\r\n\r\n") !== false) {
                    break;
                }
                
                usleep(100000); // 100ms pause between reads
            }

            return true;

        } catch (Exception $e) {
            error_log("WebSocket error: " . $e->getMessage());
            throw $e;
        } finally {
            if ($this->socket) {
                fclose($this->socket);
            }
        }
    }

    private function generateHeaders($message) {
        $key = base64_encode(random_bytes(16));
        $host = $this->host;
        $port = $this->port;
        $len = strlen($message);
        
        return "GET / HTTP/1.1\r\n" .
               "Host: $host:$port\r\n" .
               "Upgrade: websocket\r\n" .
               "Connection: Upgrade\r\n" .
               "Sec-WebSocket-Key: $key\r\n" .
               "Sec-WebSocket-Version: 13\r\n" .
               "Content-Length: $len\r\n\r\n";
    }

    public function notify($type, $message, $data = []) {
        return $this->send([
            'type' => $type,
            'message' => $message,
            'data' => $data
        ]);
    }
}