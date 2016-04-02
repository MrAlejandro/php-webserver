<?php

class HttpServer
{
    protected $_socket = null;

    const HOST_NAME = '127.0.0.1';
    const HOST_PORT = '8080';
    const CLRF = "\r\n";

    public function __construct()
    {
        echo 'Starting server' . PHP_EOL;

        try {
            $this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if ($this->_socket === false) {
                throw new Exception();
            }

            if (socket_bind($this->_socket, self::HOST_NAME, self::HOST_PORT) === false) {
                throw new Exception();
            }

            if (socket_listen($this->_socket, 5) === false) {
                throw new Exception();
            }

            $this->_startWaiting();

        } catch (Exception $e) {
            $this->_handleError($e);
        }
    }

    protected function _startWaiting()
    {
        try {
            while (true) {
                echo 'still running' . PHP_EOL;

                $response = array();

                echo 'before fail' . PHP_EOL;

                if (($socket = socket_accept($this->_socket)) === false) {
                    break;
                }

                echo 'start_processing' . PHP_EOL;

                $http_request = array();
                $new_line_qty = 0;
                $i = 1;

                while (true) {

                    if (($buf = socket_read($socket, 2048, PHP_NORMAL_READ)) === false) {
                        break;
                    }

                    $buf = str_replace(array("\n", "\r"), '', $buf);

                    if ($buf) {
                        preg_match('/(.+)?:\s(.+)/', trim($buf), $matches);

                        if (!empty($matches[1]) && !empty($matches[2])) {
                            $http_request['headers'][$matches[1]] = $matches[2];
                        } else {
                            $http_request['headers'][] = $buf;
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

                print_r($http_request);
                list($method, $path, $protocol) = $this->_parseStatusLine($http_request['headers'][0]);

                if ($path == 'favicon.ico') {
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
                    $response['body'] = 'My application body';

                    $response['content_length'] = 'Content-Length: ' . strlen($response['body']);
                }

                $response = implode(self::CLRF, $response);

                socket_write($socket, $response, strlen($response));

                echo 'closing socket' . PHP_EOL;

                socket_close($socket);
            }
        } catch (Exceprion $e) {
            $this->_handleError($e);
        }

        socket_close($this->_socket);
    }


    protected function _handleError($e)
    {
        if ($this->_socket === false) {
            $message = socket_strerror(socket_last_error());
        } else {
            $message = socket_strerror(socket_last_error($this->_socket));
        }

        echo 'Failed starting server: ' . $message . PHP_EOL;
    }

    protected function _parseStatusLine($statusLine)
    {
        preg_match('/(\w+)\s(.+)?\s(.+)/', $statusLine, $matches);
        return array($matches[1], $matches[2], $matches[3]);
    }

    public function __destruct()
    {
        echo 'Sutting down server' . PHP_EOL;
        socket_close($this->_socket);
    }
}
