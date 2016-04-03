<?php

class HttpClient
{
    private $_socket = false;
    private $_path = false;
    private $_method = false;
    private $_protocol = false;
    private $_request = array();
    private $_body = false;

    public function __construct($socket)
    {
        $this->_socket = $socket;
    }

    public function startRoutines()
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            // return cild pid to parent process
            return $pid;
        }

        echo 'start_processing' . PHP_EOL;

        $resieved_length = 0;
        $new_line_qty = 0;
        $i = 1;

        while (true) {

            /* socket_recv($this->_socket, $buf, 10240, MSG_DONTWAIT); */
            /* var_dump($buf); */
            /* exit; */
            if (($buf = socket_read($this->_socket, 2048, PHP_NORMAL_READ)) === false) {
                break;
            }

            $resieved_length += strlen($buf);
            $buf = str_replace(array("\n", "\r"), '', $buf);

            if ($buf) {
                preg_match('/(.+)?:\s(.+)/', trim($buf), $matches);

                if (!empty($matches[1]) && !empty($matches[2])) {
                    $this->_request['headers'][$matches[1]] = $matches[2];
                } else {
                    $this->_request['headers'][] = $buf;
                }

                $new_line_qty = 0;
            } else {
                $new_line_qty++;
            }

            // headers ended
            if ($new_line_qty > 2) {
                break;
            }
            $i++;
        }

        print_r($this->_request);

        if (!empty($this->_request['headers']['Content-Length'])) {
            socket_recv($this->_socket, $this->_body, $this->_request['headers']['Content-Length'], MSG_DONTWAIT);
            echo $this->_body . PHP_EOL;
        }

        if (!empty($this->_request['headers'][0])) {
            list($this->_method, $this->_path, $this->_protocol) = $this->_parseStatusLine($this->_request['headers'][0]);
        }

        $this->_doAnswer();

        echo 'closing socket' . PHP_EOL;

    }

    private function _doAnswer()
    {
        if ($this->_path == '/favicon.ico') {
            $response[] = 'HTTP/1.1 200 OK';
            $response[] = 'Accept-Ranges: bytes';
            $response[] = 'Content-Type: image/x-icon';
            $response['content_length'] = '';
            $response[] = '';
            $response['body'] = file_get_contents('favicon.ico');
            $response['content_length'] = 'Content-Length: ' . strlen($response['body']);
        } else {
            $response[] = 'HTTP/1.1 200 OK';
            $response[] = 'Connection: close';
            $response[] = 'Content-Type: text/html';
            $response['content_length'] = '';
            $response[] = '';
            if ($this->_body !== false) {
                $response['body'] = $this->_body;
            } else {
            $response['body'] = '<form method="POST" action="http://127.0.0.1:8080/">First name:<br>
  <input type="text" name="firstname" value="Mickey"><br>
  Last name:<br>
  <input type="text" name="lastname" value="Mouse"><br><br>
  <input type="submit" value="Submit">
</form>';
            }

            $response['content_length'] = 'Content-Length: ' . strlen($response['body']);
        }

        $response = implode("\r\n", $response);

        socket_write($this->_socket, $response, strlen($response));

        socket_shutdown($this->_socket);
        socket_close($this->_socket);
        exit;
        /* $this->_closeConnection(); */
    }

    private function _parseStatusLine($statusLine)
    {
        preg_match('/(\w+)\s(.+)?\s(.+)/', $statusLine, $matches);
        return array($matches[1], $matches[2], $matches[3]);
    }

    private function _closeConnection()
    {
        $this->__destruct();
        exit;
    }

    public function __destruct()
    {
    }
}
