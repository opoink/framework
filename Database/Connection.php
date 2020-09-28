<?php
/**
* Copyright 2018 Opoink Framework (http://opoink.com/)
* Licensed under MIT, see LICENSE.md
*/
namespace Of\Database;

class Connection {

    /**
     * hold's the config value for database connection
     * this variable is set during the opoink installation
     */
    private $config = [
        'driver' => '',
        'username' => '',
        'password' => '',
        'database' => '',
        'host' => '',
        'table_prefix' => '',
    ];

    /**
     * this holds the current database connection
     * this will be closed before the application ends
     */
    private $connection;

    /**
     * set the config of database connection
     * if the parameters has value
     * the config from <installation dir>/etc/database.php will be overiden
     * @param $driver currently only "Pdo_Mysql" driver is supported
     * @param $username
     * @param $password
     * @param $database
     * @param $host
     * @param $table_prefix is the prefix added during the installation
     */
    public function setConfig($driver=null, $username=null, $password=null, $database=null, $host=null, $table_prefix=null){
        if($driver != null && $username != null && $password != null && $database != null && $host != null && $table_prefix != null){
            $this->config = [
                'driver' => $driver,
                'username' => $username,
                'password' => $password,
                'database' => $database,
                'host' => $host,
                'table_prefix' => $table_prefix,
            ];
        } else {
            $configFile = ROOT.DS.'etc'.DS.'database.php';
            if(file_exists($configFile)){
                $this->config = require_once($configFile);
            }
        }
        return $this;
    }

    /**
     * connect to database depends on the set driver
     * opoink currently suported driver is "Pdo_Mysql"
     * other driver will be added on the future updates
     */
    public function connect(){
        switch ($this->config['driver']) {
            case 'Pdo_Mysql':
                $this->connection = new \Of\Database\Drivers\PdoDriver();
                $this->connection->connect($this->config);
                break;
            default:
                $this->connection = null;
        }
    }

    public function getConnection(){
        if($this->connection){
            return $this->connection->getConnection();
        } else {
            $this->setConfig()->connect();
            return $this->getConnection();
        }
    }
}
?>