<?php

class HttpServer
{
    protected $_socket = null;
    protected $_processes = array();

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

            /* socket_set_nonblock($this->_socket); */

            $this->_startWaiting();

        } catch (Exception $e) {
            $this->_handleError($e);
        }
    }

    protected function _startWaiting()
    {
        try {
            while (true) {
                $response = array();

                echo 'waiting' . PHP_EOL;
                if (($socket = socket_accept($this->_socket)) === false) {
                    break;
                }

                $client = new HttpClient($socket);
                $this->_processes[] = $client->startRoutines();

                /* $this->_killZombieProcesses(); */

                echo 'finished' . PHP_EOL;
            }
        } catch (Exceprion $e) {
            $this->_handleError($e);
        }

    }

    protected function _killZombieProcesses()
    {
        foreach ($this->_processes as $key => $process) {
            echo $process . PHP_EOL;
            echo 'ps ' . $process . ' | grep "Z+"';
            $output = shell_exec('ps ' . $process . ' | grep "Z+"');
            echo $output . PHP_EOL;
            var_dump(strpos($output, (string) $process) !== false);
            if (strpos($output, (string) $process) !== false) {
                echo 'kill ' . $process . PHP_EOL;
                exec('kill ' . $process);
                unset($this->_processes[$key]);
            }
        }

        return $this;
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


    public function __destruct()
    {
        echo 'Sutting down server' . PHP_EOL;
        socket_close($this->_socket);
    }
}
