<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Dcms_Model_Worker
 *
 * @author gconstantino
 */
class Dcms_Model_Worker {

    //put your code here


    private $_worker;
    private $_workerTerminate = false;
    private $_maxIteration = 10;

    public function __construct() {
        $this->_worker = new GearmanWorker();
        $this->_worker->setTimeout(60000);
        $this->_worker->addOptions(GEARMAN_WORKER_NON_BLOCKING | GEARMAN_CLIENT_FREE_TASKS);
    }

    public function startWorker(Array $credentials) {
        $gearmanServers = $credentials['gearmanServers'];
        $functionAlias = $credentials['alias'];
        $functionName = $credentials['functionname'];
        $class = $credentials['class'];
        $addServer = $this->addServers($gearmanServers);
        $addFunction = $this->addFunction($functionAlias, $functionName, $class);
        
        $worker = $this;
        pcntl_signal(SIGTERM, function() use ($worker) {
                        $worker->terminate(true);
                    });
                    
        $this->work();
    }

    public function addFunction($functionAlias, $functionName, $class) {
        $addWorkerFunction = $this->worker()->addFunction($functionAlias, array($class, $functionName));

        if (!$addWorkerFunction) {
            throw new Exception("Function not added");
        }
    }

    public function addServer($host, $port = 4730) {
        $addServer = $this->worker()->addServer($host, $port);
        if (!$addServer) {
            throw new Exception("Unable to add server: " . $host . ":" . $port);
        }
    }

    public function addServers($servers = "127.0.0.1:4730") {
        $addServers = $this->worker()->addServers($servers);
        if (!$addServers) {
            throw new Exception("Unable to add servers: " . $servers);
        }
    }

    public function worker() {
        return $this->_worker;
    }

    /**
     * Invokes GearmanWorker's work
     */
    public function work() {
        $worker = $this->worker();
        $iter = 0;
        $this->verbose(date("Y-m-d H:i:s Z\t", time()) . "Waiting for Task...\n");
        
        while (!$this->_workerTerminate && ($worker->work() ||
        $worker->returnCode() == GEARMAN_IO_WAIT ||
        $worker->returnCode() == GEARMAN_NO_JOBS ||
        $worker->returnCode() == GEARMAN_TIMEOUT)) {
            $iter++;
            if ($worker->returnCode() == GEARMAN_SUCCESS) {
                if ($iter > $this->_maxIteration) {
                    $this->terminate();
                }
                $iter = 0;
                $this->verbose(date("Y-m-d H:i:s Z\t", time()) . "[Iter = {$iter} ] Task successfully executed.\n\n\n");
                continue;
            }

            if (!@$worker->wait()) {
                switch ($worker->returnCode()) {
                    case GEARMAN_NO_ACTIVE_FDS :
                        $this->verbose(date("Y-m-d H:i:s Z\t", time()) . "[Iter = {$iter} ] No active connection to the Gearman server\n");
                        break;

                    case GEARMAN_TIMEOUT:
                        $this->verbose(date("Y-m-d H:i:s Z\t", time()) . "[Iter = {$iter} ] Timedout waiting for new job.\n");
                        break;

                    default:
                        $this->verbose(date("Y-m-d H:i:s Z\t", time()) . "[Iter = {$iter} ] ReturnCode = " . $worker->returnCode() . "\n");
                }

                sleep(2);
            }

            if ($iter > $this->_maxIteration) {
                $this->terminate();
            }
        } //while

        $this->verbose("[Iter = {$iter} ] Terminating.\n\n\n\n");
    }
    
    
    public function verbose($message){
        echo $message;
    }

    /**
     * Set the terminate flag so that gearman worker loop will break.
     */
    public function terminate() {
        $this->_workerTerminate = true;
    }

    public function returnCode($returnCode) {
        $returnCodeMessages = array(
            GEARMAN_SUCCESS => "Whatever action was taken was successful.",
            GEARMAN_IO_WAIT => "When in non-blocking mode, an event is hit that would have blocked.",
            GEARMAN_SHUTDOWN => "GEARMAN_SHUTDOWN is a special case. If it is returned the client will be sent GEARMAN_SUCCESS, but gearman_worker_work() will exit with GEARMAN_SHUTDOWN.",
            GEARMAN_SHUTDOWN_GRACEFUL => "This is a server only related error, and will not be found in any client or worker return",
            GEARMAN_ERRNO => "System error occurred. Use either gearman_client_errno() or gearman_worker_errno()",
            GEARMAN_EVENT => "This is a server only related error, and will not be found in any client or worker return.",
            GEARMAN_TOO_MANY_ARGS => "The connection will be dropped/reset.",
            GEARMAN_NO_ACTIVE_FDS => "No active connections were available. gearman_continue() can be used to test for this.",
            GEARMAN_INVALID_MAGIC => "The connection will be dropped/reset.",
            GEARMAN_INVALID_COMMAND => "The connection will be dropped/reset.",
            GEARMAN_INVALID_PACKET => "The connection will be dropped/reset.",
            GEARMAN_UNEXPECTED_PACKET => "The connection will be dropped/reset.",
            GEARMAN_GETADDRINFO => "DNS resolution failed (invalid host, port, etc).",
            GEARMAN_NO_SERVERS => "Did not call GearmanClient::addServer() before submitting jobs or tasks.",
            GEARMAN_LOST_CONNECTION => "Lost a connection during a request.",
            GEARMAN_MEMORY_ALLOCATION_FAILURE => "Memory allocation failed (ran out of memory).",
            GEARMAN_JOB_EXISTS => "gearman_client_job_status() has been called for a gearman_job_handle_t and the Job is currently known by a server, but is not being run by a worker.",
            GEARMAN_JOB_QUEUE_FULL => "GEARMAN_JOB_QUEUE_FULL",
            GEARMAN_SERVER_ERROR => "Something went wrong in the Gearman server and it could not handle the request gracefully.",
            GEARMAN_WORK_ERROR => "A task has had an error and will be retried.",
            GEARMAN_WORK_DATA => "Notice return code obtained with GearmanClient::returnCode() when using GearmanClient::do(). Sent to update the client with data from a running job. A worker uses this when it needs to send updates, send partial results, or flush data during long running jobs.",
            GEARMAN_WORK_WARNING => "Notice return code obtained with GearmanClient::returnCode() when using GearmanClient::do(). Updates the client with a warning. The behavior is just like GEARMAN_WORK_DATA, but should be treated as a warning instead of normal response data.",
            GEARMAN_WORK_STATUS => "Notice return code obtained with GearmanClient::returnCode() when using GearmanClient::do(). Sent to update the status of a long running job. Use GearmanClient::doStatus() to obtain the percentage complete of the task.",
            GEARMAN_WORK_EXCEPTION => "Notice return code obtained with GearmanClient::returnCode() when using GearmanClient::do(). Indicates that a job failed with a given exception.",
            GEARMAN_WORK_FAIL => "Notice return code obtained with GearmanClient::returnCode() when using GearmanClient::do(). Indicates that the job failed.",
            GEARMAN_NOT_CONNECTED => "Client/Worker is not currently connected to the server.",
            GEARMAN_COULD_NOT_CONNECT => "Failed to connect to servers.",
            GEARMAN_SEND_IN_PROGRESS => "GEARMAN_SEND_IN_PROGRESS",
            GEARMAN_RECV_IN_PROGRESS => "GEARMAN_RECV_IN_PROGRESS",
            GEARMAN_NOT_FLUSHING => "gearman_task_send_workload() failed, it was not in the correct state.",
            GEARMAN_DATA_TOO_LARGE => "gearman_task_send_workload() failed, the data was too large to be sent.",
            GEARMAN_INVALID_FUNCTION_NAME => "A worker was sent a request for a job that it did not have a valid function for.",
            GEARMAN_INVALID_WORKER_FUNCTION => "No callback was provided by the worker for a given function.",
            GEARMAN_NO_REGISTERED_FUNCTIONS => "The worker has not registered any functions.",
            GEARMAN_NO_JOBS => "No jobs were found for the worker.",
            GEARMAN_ECHO_DATA_CORRUPTION => "Either gearman_client_echo() or gearman_worker_echo() echo was unsuccessful because the data was returned from gearmand corrupted.",
            GEARMAN_NEED_WORKLOAD_FN => "A client was asked for work, but no gearman_workload_fn callback was specified. See gearman_client_set_workload_fn()",
            GEARMAN_PAUSE => "Used only in custom application for client return based on GEARMAN_WORK_DATA, GEARMAN_WORK_WARNING, GEARMAN_WORK_EXCEPTION, GEARMAN_WORK_FAIL, or GEARMAN_WORK_STATUS. gearman_continue() can be used to check for this value.",
            GEARMAN_UNKNOWN_STATE => "The gearman_return_t was never set.",
            GEARMAN_PTHREAD => "GEARMAN_PTHREAD",
            GEARMAN_PIPE_EOF => "GEARMAN_PIPE_EOF",
            GEARMAN_QUEUE_ERROR => "GEARMAN_QUEUE_ERROR",
            GEARMAN_FLUSH_DATA => "GEARMAN_FLUSH_DATA",
            GEARMAN_SEND_BUFFER_TOO_SMALL => "Internal error: trying to flush more data in one atomic chunk than is possible due to hard-coded buffer sizes.",
            GEARMAN_IGNORE_PACKET => "GEARMAN_IGNORE_PACKET",
            GEARMAN_UNKNOWN_OPTION => "Default state of task return value.",
            GEARMAN_MAX_RETURN => "GEARMAN_MAX_RETURN",
        );

        return $returnCodeMessages[$returnCode];
    }

}

?>
