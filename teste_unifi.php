<?php 

require_once 'vendor/autoload.php';

$db_username = 'root';
$db_password = ''; 
$db_host = 'localhost';

//mysqli
$mysqli = new mysqli($db_host, $db_username, $db_password);
$mysqli->query("CREATE SCHEMA IF NOT EXISTS  controller_unifi");
$mysqli->query("use controller_unifi");
// $mysqli->query("DROP TABLE ap_actual");
init($mysqli);

$url   = "https://zabbix-noc.americanet.com.br/zabbix/api_jsonrpc.php";
$user  = "suporte";
$senha = "Amnet@2022";

$auth                       = auth($url,$user,$senha);
$templateArray              = getTemplateID($url,$auth,"Template Integracao Api Unifi");
$proxys                     = getProxy($url,$auth);
$proxyID                    = 1;
$arrayUnifiController       = getHosts($url,$auth,$templateArray,$proxyID);

$arrayUnifiController       = array_slice($arrayUnifiController, intval($argv[2]),intval($argv[3]) );

// exit();

$arrayUnifiController = array(
    array(
        "name"          => "CONTROLLER UNIFI 1",
        "ip"            => "127.0.0.1",
        "url"           => "https://unifi6-extel.compl.eti.br",
        "user"          => "flowbix",
        "pass"          => '@1Flowbix$',
        "id_s"          => "mybjmk7y",
        "version"       => "7.3.83",
        'ssl'           => false,
        "controllerid"  => 1
    )
);

foreach($arrayUnifiController as $con){

    $url             = $con["url"];
    $user            = $con["user"];
    $pass            = $con["pass"];
    $id_s            = $con["id_s"];
    $version         = $con["version"]; 
    $ssl             = $con["ssl"];
    $controller_id   = $con["controllerid"];
    $controller_name = $con["name"]; 
    $controller_ip   = $con["ip"];
    $controller_url  = $con["url"]; 

    $unifi_connection = new UniFi_API\Client($user, $pass, $url , "" ,$version , $ssl);
    $login            = $unifi_connection->login();
    $list_of_aps      = $unifi_connection->list_devices();
    $list_of_sites    = $unifi_connection->list_sites(); 
    $list_of_clients  = $unifi_connection->list_clients(); 
    
    if($argv[1] == "APS"){

        foreach($list_of_sites as $st){

            $site_id    = $st->{'name'};
            
            $unifi_connection = new UniFi_API\Client($user, $pass, $url , $site_id ,$version , $ssl);
            $login            = $unifi_connection->login();
            $list_of_aps      = $unifi_connection->list_devices();
            $list_of_sites    = $unifi_connection->list_sites(); 
            $list_of_clients  = $unifi_connection->list_clients(); 

            $lis_of_aps = array();

            foreach($list_of_aps as $ap){

                $mac                = $ap->{'mac'};        
                $state              = $ap->{'state'};
                $ap_ip              = $ap->{'ip'};
                if($state == 1){
                    $uptime             = $ap->{'system-stats'}->uptime;
                    $cpu                = $ap->{'system-stats'}->cpu;
                    $memo               = $ap->{'system-stats'}->mem;
                    $upload             = $ap->{'uplink'}->{'tx_bytes-r'};
                    $download           = $ap->{'uplink'}->{'rx_bytes-r'};
                    $name               = $ap->{'name'};
                    $satisfaction       = $ap->{'satisfaction'};
                    $version            = $ap->{'displayable_version'};
                    $model              = $ap->{'model'};
                    $ap_site_id         = $ap->{'site_id'};
                    $ap_adopted         = $ap->{'adopted'};
                    $ap_connection      = $ap->{'uplink'}->{'type'};
                    $ap_upgradable      = $ap->{'upgradable'};
    
                }else{
                    $uptime             = time() - $ap->{'disconnected_at'};
                    $cpu                = 0;
                    $memo               = 0;
                    $upload             = 0;
                    $download           = 0;
                    $name               = $ap->{'name'};
                    $satisfaction       = 0;
                    $version            = "";
                    $model              = $ap->{'model'};
                    $ap_site_id         = $ap->{'site_id'};
                    $ap_adopted         = $ap->{'adopted'};
                    $ap_connection      = $ap->{'uplink'}->{'type'};
                    $ap_upgradable      = $ap->{'upgradable'};
                }
                if($name == 'Obra-Substacao-01'){
                    // error_log(print_r(json_encode($ap),true));
                }
                
                $lis_of_aps[$mac]["qtd_users"]          = 0;
                $lis_of_aps[$mac]["uptime"]             = $uptime;
                $lis_of_aps[$mac]["ap_ip"]              = $ap_ip;
                $lis_of_aps[$mac]["cpu"]                = $cpu;
                $lis_of_aps[$mac]["memo"]               = $memo;
                $lis_of_aps[$mac]["upload"]             = $upload;
                $lis_of_aps[$mac]["download"]           = $download;
                $lis_of_aps[$mac]["name"]               = $name;
                $lis_of_aps[$mac]["satisfaction"]       = $satisfaction;
                $lis_of_aps[$mac]["state"]              = $state;
                $lis_of_aps[$mac]["version"]            = $version;
                $lis_of_aps[$mac]["model"]              = $model;
                $lis_of_aps[$mac]["ap_site_id"]         = $ap_site_id;
                $lis_of_aps[$mac]["ap_adopted"]         = $ap_adopted;
                $lis_of_aps[$mac]["ap_connection"]      = $ap_connection;
                $lis_of_aps[$mac]["ap_upgradable"]      = $ap_upgradable;
            }
    
            foreach($list_of_clients as $c){
                $lis_of_aps[$c->ap_mac]["qtd_users"]++;
            }
    
            foreach($lis_of_aps as $mac => $ap){
                
                $ap_mac             = $mac;
                $ap_ip              = $ap["ap_ip"];
                $ap_site_id         = $ap["ap_site_id"];
                $ap_name            = $ap["name"];
                $ap_users           = $ap["qtd_users"];
                $ap_uptime          = $ap["uptime"];
                $ap_cpu             = $ap["cpu"];
                $ap_memo            = $ap["memo"]; 
                $ap_upload          = $ap["upload"];
                $ap_download        = $ap["download"];
                $ap_satisfaction    = $ap["satisfaction"];
                $ap_state           = $ap["state"];
                $ap_version         = $ap["version"];
                $ap_model           = $ap["model"]; 
                $ap_adopted         = $ap["ap_adopted"]; 
                $ap_connection      = $ap["ap_connection"]; 
                $ap_upgradable      = $ap["ap_upgradable"]; 
    
                $resultSelect      = $mysqli->query("SELECT * FROM ap_actual WHERE ap_mac = '$ap_mac'  AND controller_id= '$controller_id' and ap_site_id = '$ap_site_id'");
    
                if ($resultSelect->num_rows > 0) {
                    $mysqli->query("UPDATE ap_actual SET ap_ip='$ap_ip',ap_name='$ap_name',ap_users=$ap_users,ap_uptime=$ap_uptime,ap_cpu=$ap_cpu,ap_memo=$ap_memo,ap_upload=$ap_upload,
                    ap_download=$ap_download,ap_satisfaction=$ap_satisfaction,ap_state=$ap_state,ap_version='$ap_version',ap_model='$ap_model',
                    ap_adopted='$ap_adopted',ap_connection='$ap_connection',ap_upgradable='$ap_upgradable'
                    ,update_date=NOW()
                    WHERE ap_mac = '$ap_mac'  AND controller_id= '$controller_id' and ap_site_id = '$ap_site_id'");       
                }else{
                    $mysqli->query("INSERT INTO ap_actual(ap_ip,ap_site_id,ap_mac,ap_name,ap_users,ap_uptime,ap_cpu,ap_memo,ap_upload,ap_download,ap_satisfaction,ap_state,ap_version,ap_model,controller_id
                    ,ap_adopted,ap_connection,ap_upgradable) VALUES
                    ('$ap_ip','$ap_site_id','$ap_mac','$ap_name',$ap_users,$ap_uptime,$ap_cpu,$ap_memo,$ap_upload,$ap_download,$ap_satisfaction,$ap_state,'$ap_version','$ap_model','$controller_id'
                    ,'$ap_adopted','$ap_connection','$ap_upgradable')");
                }
                if($mysqli->error){
                    error_log(print_r($mysqli->error. ' AA',true));
                }
                // $mysqli->query("INSERT INTO ap_hist(ap_name,ap_mac,ap_users,ap_uptime,ap_cpu,ap_memo,ap_upload,ap_download,ap_satisfaction,ap_state,controller_id) VALUES
                //     ('$ap_name','$ap_mac',$ap_users,$ap_uptime,$ap_cpu,$ap_memo,$ap_upload,$ap_download,$ap_satisfaction,$ap_state,'$controller_id')");
                // if($mysqli->error){
                //     error_log(print_r($mysqli->error,true));
                // }
            }
        }
       
        // error_log(print_r($lis_of_aps,true));
    }elseif($argv[1] == "SITE_ID"){
        foreach($list_of_sites as $st){

            error_log(print_r($st,true));
            $site_id    = $st->{'_id'};
            $site_desc  = $st->{'desc'};
            $resultSelect      = $mysqli->query("SELECT * FROM site_info WHERE site_id = '$site_id'  AND controller_id= '$controller_id' ");

            if ($resultSelect->num_rows > 0) {
                $mysqli->query("UPDATE site_info SET site_desc='$site_desc',update_date=NOW()
                WHERE  controller_id= '$controller_id' and site_id = '$site_id'");       
            }else{
                $mysqli->query("INSERT INTO site_info(site_id,site_desc,controller_id) VALUES
                ('$site_id','$site_desc','$controller_id')");
            }
            if($mysqli->error){
                error_log(print_r($mysqli->error,true));
            }
        }
    }
    elseif($argv[1] == "LIST_HOSTS"){

        $resultSelect      = $mysqli->query("SELECT * FROM controller_infos WHERE  controller_id= '$controller_id' ");

        if ($resultSelect->num_rows > 0) {
            $mysqli->query("UPDATE controller_infos SET controller_name='$controller_name',controller_ip='$controller_ip',controller_url='$controller_url',update_date=NOW()
            WHERE  controller_id= '$controller_id' ");       
        }else{
            $mysqli->query("INSERT INTO controller_infos(controller_id,controller_name,controller_ip,controller_url) VALUES
            ('$controller_id','$controller_name','$controller_ip','$controller_url')");
        }
        if($mysqli->error){
            error_log(print_r($mysqli->error,true));
        }
    
    }
}

function getProxy($url,$auth){

    // Interface Get
    $DataProxy = array(
        "jsonrpc" => "2.0",
        "method" => "proxy.get",
        "params" => array(
            "output"       => 'extend',  
        ),
        "auth" => $auth,
        "id" => 1
    );

    $DataProxy = array(
        'http' => array(
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => json_encode($DataProxy)
        ),
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ),
    
    );
    $ContentProxy  = stream_context_create($DataProxy);
    $ResultProxy = json_decode(file_get_contents($url, false, $ContentProxy),true);

    $Proxys = array();

    foreach ($ResultProxy["result"] as $key => $value){

        $Proxys[$value["host"]] = $value["proxyid"];
    }

    return $Proxys;
}

function getHosts($url,$auth,$TemplateArray,$ProxyID){

    // Interface Get
    if($ProxyID == 1){
        $DataProxy = array(
            "jsonrpc" => "2.0",
            "method" => "host.get",
            "params" => array(
                "output"            => 'extend',  
                "templateids"       => $TemplateArray,
                "selectInterfaces"  => "extend",
                "selectMacros"      => "extend",
                "selectInventory" => "extend"
            ),
            "auth" => $auth,
            "id" => 1
        );
    }else{
        $DataProxy = array(
            "jsonrpc" => "2.0",
            "method" => "host.get",
            "params" => array(
                "output"            => 'extend',  
                "templateids"       => $TemplateArray,
                "selectInterfaces"  => "extend",
                "selectMacros"      => "extend",
                "proxyids"          => $ProxyID,
                "selectInventory" => "extend"

            ),
            "auth" => $auth,
            "id" => 1
        );
    }

    $DataProxy = array(
        'http' => array(
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => json_encode($DataProxy)
        ),
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ),
    
    );
    $ContentProxy  = stream_context_create($DataProxy);
    $ResultProxy = json_decode(file_get_contents($url, false, $ContentProxy),true);

    $AHost = array();
    foreach ($ResultProxy["result"] as $key => $value){
        $a = array();
        $snmp = "";
        $ip = "";

        foreach($value["interfaces"] as $key3 => $value3){
            if($value3["type"] == '2'){
                $snmp      = $value3["details"]["community"];
                $ip      = $value3["ip"];
            }
        }
        foreach($value["macros"] as $key2 => $value2){
            if($value2["macro"] == '{$SNMP_COMMUNITY}'){
                $snmp      = $value2["value"];
            }
        }

        
        // $a["ip"]        = $ip;
        // $a["community"] = $snmp;
        // $a["nome"]      = $value["name"];
        // $a["count_olt"] = $value["hostid"];
        // $a["location"]  = $value["inventory"]["location"];


        $a["name"]           = $value["name"];
        $a["ip"]             = "ip";
        $a["url"]            = "url";
        $a["user"]           = "user";
        $a["pass"]           = "pass";
        $a["id_s"]           = "id_s";
        $a["version"]        = "version";
        $a["ssl"]            = "ssl";
        $a["controllerid"]   = $value["hostid"];

        $AHost[] = $a;
    }

    return $AHost;
}

function getTemplateID($url,$auth,$templateName){

    $DataProxy = array(
        "jsonrpc" => "2.0",
        "method" => "template.get",
        "params" => array(
            "output"            => 'extend',  
            "filter" => [
                "host" => "$templateName"
            ]
        ),
        "auth" => $auth,
        "id" => 1
    );

    $DataProxy = array(
        'http' => array(
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => json_encode($DataProxy)
        ),
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ),
    
    );
    $ContentProxy  = stream_context_create($DataProxy);
    $ResultProxy = json_decode(file_get_contents($url, false, $ContentProxy),true);

    $TemplateID = array();

    foreach ($ResultProxy["result"] as $key => $value){

        $TemplateID = $value["templateid"];
       
    }

    return $TemplateID;
}

function auth($url,$login,$pwd){
    $data = array(
        "jsonrpc"=> "2.0",
        "method"=> "user.login",
        "params"=> array(
            "user"=> "$login",
            "password"=> "$pwd"
        ),
        "id"=> 1,
        "auth"=> null 
    );

    // Auth Request
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => json_encode($data)
        ),
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ),
    
    );

    $context  = stream_context_create($options);
    $result = json_decode(file_get_contents($url, false, $context),true);
    return  $result["result"];

}

function TableNotExists($mysqli,$table) {
    $res = $mysqli->query("SELECT 1 FROM $table");

    if(isset($res->num_rows)) {
        return false;
    } else return true;
}

function init($mysqli){
    
    if ($mysqli->connect_error) { 
        error_log("Erro ao conectar com banco, error: " . $mysqli->connect_error); 
        exit();
    }else{
        if(TableNotExists($mysqli,'ap_actual')){

            $sql = "CREATE TABLE ap_actual ( ".
                    "id                          int(11) NOT NULL AUTO_INCREMENT,".
                    "ap_site_id                  varchar(50) DEFAULT NULL,".
                    "ap_mac                      varchar(50) DEFAULT NULL,".
                    "ap_ip                       varchar(30) DEFAULT NULL,".
                    "ap_name                     varchar(100) DEFAULT NULL,".
                    "ap_users                    int(5) DEFAULT NULL,".
                    "ap_uptime                   bigint DEFAULT NULL,".
                    "ap_cpu                      DECIMAL(6,2) DEFAULT NULL,".
                    "ap_memo                     DECIMAL(6,2) DEFAULT NULL,".
                    "ap_upload                   BIGINT DEFAULT NULL,".
                    "ap_download                 BIGINT DEFAULT NULL,".
                    "ap_satisfaction             DECIMAL(6,2) DEFAULT NULL,".
                    "ap_state                    INT(2) DEFAULT NULL,".
                    "ap_version                  VARCHAR(20) DEFAULT NULL,".
                    "ap_model                    VARCHAR(20) DEFAULT NULL,".
                    "ap_adopted                  VARCHAR(20) DEFAULT NULL,".
                    "ap_connection               VARCHAR(20) DEFAULT NULL,".
                    "ap_upgradable               VARCHAR(20) DEFAULT NULL,".
                    "controller_id               int(6) DEFAULT NULL,".
                    "update_date                 timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,".
    
                    "PRIMARY KEY (`id`)".
                    " ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
        

            if ($mysqli->query($sql) === TRUE) {
                error_log('Foi criada a tabela: ap_actual');
            } else {
                error_log("Ocorreu algum erro ao tentar criar a tabela 'ap_actual': " . $mysqli->error);
                error_log(print_r($sql, true));
                exit("Erro ao criar tabela\n");  
            }  
        } 
        if(TableNotExists($mysqli,'ap_hist')){
            
            $sql = "CREATE TABLE ap_hist ( ".
                    "id                          int(11) NOT NULL AUTO_INCREMENT,".
                    "ap_name                     varchar(100) DEFAULT NULL,".
                    "ap_mac                      varchar(50) DEFAULT NULL,".
                    "ap_users                    int(5) DEFAULT NULL,".
                    "ap_uptime                   bigint DEFAULT NULL,".
                    "ap_cpu                      DECIMAL(6,2) DEFAULT NULL,".
                    "ap_memo                     DECIMAL(6,2) DEFAULT NULL,".
                    "ap_upload                   BIGINT DEFAULT NULL,".
                    "ap_download                 BIGINT DEFAULT NULL,".
                    "ap_satisfaction             DECIMAL(6,2) DEFAULT NULL,".
                    "ap_state                    INT(2) DEFAULT NULL,".
                    "controller_id               int(6) DEFAULT NULL,".
                    "hist_date                   timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,".
    
                    "PRIMARY KEY (`id`)".
                    " ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
        

            if ($mysqli->query($sql) === TRUE) {
                error_log('Foi criada a tabela: ap_hist');
            } else {
                error_log("Ocorreu algum erro ao tentar criar a tabela 'ap_hist': " . $mysqli->error);
                error_log(print_r($sql, true));
                exit("Erro ao criar tabela\n");  
            }  
        } 

        if(TableNotExists($mysqli,'controller_infos')){
            
            $sql = "CREATE TABLE controller_infos ( ".
                    "id                          int(11) NOT NULL AUTO_INCREMENT,".
                    "controller_id              int(6) DEFAULT NULL,".
                    "controller_name             varchar(100) DEFAULT NULL,".
                    "controller_ip               varchar(150) DEFAULT NULL,".
                    "controller_url              varchar(150) DEFAULT NULL,".
                    "update_date                 timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,".
                    "PRIMARY KEY (`id`)".
                    " ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
        

            if ($mysqli->query($sql) === TRUE) {
                error_log('Foi criada a tabela: controller_infos');
            } else {
                error_log("Ocorreu algum erro ao tentar criar a tabela 'controller_infos': " . $mysqli->error);
                error_log(print_r($sql, true));
                exit("Erro ao criar tabela\n");  
            }  
        } 
        if(TableNotExists($mysqli,'site_info')){
            
            $sql = "CREATE TABLE site_info ( ".
                    "id                          int(11) NOT NULL AUTO_INCREMENT,".
                    "site_id                     varchar(60) DEFAULT NULL,".
                    "site_desc                   varchar(100) DEFAULT NULL,".
                    "controller_id               int(6) DEFAULT NULL,".
                    "update_date                 timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,".
                    "PRIMARY KEY (`id`)".
                    " ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
        

            if ($mysqli->query($sql) === TRUE) {
                error_log('Foi criada a tabela: site_info');
            } else {
                error_log("Ocorreu algum erro ao tentar criar a tabela 'site_info': " . $mysqli->error);
                error_log(print_r($sql, true));
                exit("Erro ao criar tabela\n");  
            }  
        } 

    }
    
}