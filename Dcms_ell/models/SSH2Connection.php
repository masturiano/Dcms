<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Dcms_Model_SSH2Connection
 *
 * @author gconstantino
 */
class Dcms_Model_SSH2Connection {

    //put your code here


    private $_connection;

    public function __construct($host, $port, $methods = array()) {
        if (!function_exists("ssh2_connect")) {
            throw new Exception("function ssh2_connect doesn't exist");
        }
        $callbacks = array(
            'ignore' => array($this, "ignore"),
            'debug' => array($this, "debug"),
            'macerror' => array($this, "macerror"),
            'disconnect' => array($this, "closeConnection"),
        );
        $this->_connection = ssh2_connect($host, $port, $methods, $callbacks);

        if (!$this->_connection) {
            throw new Exception("Could not connect to {$host} on port {$port}.");
        }
    }

    /**
     * 
     * @param type $username
     * @param type $password
     * @throws Exception
     */
    public function login($username, $password) {
        if (!ssh2_auth_password($this->_connection, $username, $password)) {
            throw new Exception("Could not authenticate username $username.");
        }
    }

    /**
     * 
     * @param type $username
     * @param type $pubkeyFile
     * @param type $privkeyFile
     * @param type $passPhrase
     * @throws Exception
     */
    public function authPubKeyFileLogin($username, $pubkeyFile, $privkeyFile, $passPhrase = "") {
        if (!ssh2_auth_pubkey_file($this->_connection, $username, $pubkeyFile, $privkeyFile, $passPhrase)) {
            throw new Exception('Public Key Authentication Failed');
        }
    }

    /**
     * 
     * @param type $localFile
     * @param type $remoteFileDirectory
     * @param type $zipFileName
     * @param type $createMode
     * @throws Exception
     */
    public function sendFile($localFile, $remoteFileDirectory, $zipFileName, $createMode = 0644) {
        $destinationZipFile = $remoteFileDirectory . $zipFileName;
        if (!ssh2_scp_send($this->_connection, $localFile, $destinationZipFile, $createMode)) {
            throw new Exception("Could not send file {$localFile} to {$remoteFileDirectory}.");
        }
        $this->fileExists($remoteFileDirectory, $zipFileName);
    }

    /**
     * 
     * @param type $remoteFile
     * @param type $localFile
     * @throws Exception
     */
    public function recvFile($remoteFile, $localFile) {
        if (!ssh2_scp_recv($this->_connection, $remoteFile, $localFile)) {
            throw new Exception("Could not receive file {$remoteFile} to {$localFile}.");
        }
    }

    /**
     * 
     * @param type $zipFileDirectory
     * @param type $extractionDirectory
     * @param type $zipFileName
     * @param type $zipContents
     */
    public function unzipFile($zipFileDirectory, $extractionDirectory, $zipFileName, $zipContents = array()) {
//        $unzipCommand = "ssh {$this->_conection} 'unzip {$zipFileDirectory}/*.zip -d {$extractionDirectory}'";
        $unzipCommand = "unzip -o {$zipFileDirectory}/{$zipFileName} -d {$extractionDirectory}";
        $this->exec($unzipCommand);
        if (count($zipContents) > 0) {
            foreach ($zipContents as $file) {
                $this->fileExists($extractionDirectory, $file);
            }
        }
    }

    /**
     * 
     * @param type $sourceDirectoryFile
     * @param type $destination
     */
    public function moveFile($sourceDirectory, $fileName, $destinationDirectory) {
        $this->fileExists($sourceDirectory . $fileName);
        $moveCommand = "mv {$sourceDirectory}{$fileName} {$destinationDirectory}";
        $this->exec($moveCommand);
        $this->fileExists($destinationDirectory, $fileName);
    }

    /**
     * 
     * @param type $remoteFileDirectory
     * @param type $fileName
     */
    public function fileExists($remoteFileDirectory, $fileName = "") {
        if (empty($fileName)) {
            $checkCommand = "ls -l {$remoteFileDirectory}";
        } else {
            $checkCommand = "ls -l {$remoteFileDirectory}/{$fileName}";
        }

        $this->exec($checkCommand);
    }

    public function exec($command, $getReturn = false) {
        $exec = ssh2_exec($this->_connection, $command);
//        echo $command . "\n";
        if (!$exec) {
            throw new Exception("Could not execute command: {$command}");
        }
        $errStream = ssh2_fetch_stream($exec, SSH2_STREAM_STDERR);
        $dioStream = ssh2_fetch_stream($exec, SSH2_STREAM_STDIO);
        stream_set_blocking($errStream, true);
        stream_set_blocking($dioStream, true);
        $resultErr = stream_get_contents($errStream);
        $resultIo = stream_get_contents($dioStream);

        // Close the streams        
        fclose($errStream);
        fclose($dioStream);
        fclose($exec);
        if (!empty($resultErr) && !$getReturn) {
            echo "error: " . $resultErr . "\n";
            var_dump($resultErr);
            throw new Exception($resultErr);
        }
        if ($getReturn) {
            return $resultIo;
        }
    }

    public function closeConnection() {
        $this->exec("exit");
        unset($this->_connection);
    }

    public function ignore() {
        echo "igonre\n";
    }

    public function debug() {
        echo "debug\n";
    }

    public function macerror() {
        echo "macerror\n";
    }

}

?>
