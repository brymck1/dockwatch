<?php

/*
----------------------------------
 ------  Created: 021024   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Maint
{
    public function startMaintenance()
    {
        logger(MAINTENANCE_LOG, '$maintenance->startMaintenance() ->');

        $docker = dockerStartContainer($this->maintenanceContainerName);
        logger(MAINTENANCE_LOG, 'dockerStartContainer() ' . trim($docker));
    
        logger(MAINTENANCE_LOG, '$maintenance->startMaintenance() <-');
    }

    public function stopMaintenance()
    {
        logger(MAINTENANCE_LOG, '$maintenance->stopMaintenance() ->');

        $docker = dockerStopContainer($this->maintenanceContainerName);
        logger(MAINTENANCE_LOG, 'dockerStopContainer() ' . trim($docker));
    
        logger(MAINTENANCE_LOG, '$maintenance->stopMaintenance() <-');
    }

    public function removeMaintenance()
    {
        logger(MAINTENANCE_LOG, '$maintenance->removeMaintenance() ->');

        $docker = dockerRemoveContainer($this->maintenanceContainerName);
        logger(MAINTENANCE_LOG, 'dockerRemoveContainer() ' . trim($docker));
    
        logger(MAINTENANCE_LOG, '$maintenance->removeMaintenance() <-');
    }

    public function pullMaintenance()
    {
        logger(MAINTENANCE_LOG, '$maintenance->pullMaintenance() ->');

        $docker = dockerPullContainer(APP_MAINTENANCE_IMAGE);
        logger(MAINTENANCE_LOG, 'dockerPullContainer() ' . trim($docker));
    
        logger(MAINTENANCE_LOG, '$maintenance->pullMaintenance() <-');
    }

    public function createMaintenance()
    {
        $port   = intval($this->maintenancePort) > 0 ? intval($this->maintenancePort) : 9998;
        $ip     = $this->maintenanceIP;

        logger(MAINTENANCE_LOG, '$maintenance->createMaintenance() ->');
        logger(MAINTENANCE_LOG, 'using ip ' . $ip);
        logger(MAINTENANCE_LOG, 'using port ' . $port);

        $this->pullMaintenance();

        $apiResponse = apiRequest('dockerInspect', ['name' => $this->hostContainer['Names'], 'useCache' => false, 'format' => true]);
        logger(MAINTENANCE_LOG, 'dockerInspect:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));
        $inspectImage = $apiResponse['response']['docker'];
        $inspectImage = json_decode($inspectImage, true);

        $inspectImage[0]['Name']                                                = '/' . $this->maintenanceContainerName;
        $inspectImage[0]['Config']['Image']                                     = APP_MAINTENANCE_IMAGE;
        $inspectImage[0]['HostConfig']['PortBindings']['80/tcp'][0]['HostPort'] = strval($port);
        $inspectImage[0]['NetworkSettings']['Ports']['80/tcp'][0]['HostPort']   = strval($port);

        //-- STATIC IP CHECK
        if ($ip) {
            if ($inspectImage[0]['NetworkSettings']['Networks']) {
                $network = array_keys($inspectImage[0]['NetworkSettings']['Networks'])[0];
    
                if ($inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv4Address']) {
                    $inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv4Address'] = $ip;
                }
                if ($inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAddress']) {
                    $inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAddress'] = $ip;
                }
            }
        }

        $this->removeMaintenance();

        logger(MAINTENANCE_LOG, 'dockerCreateContainer() ->');
        $docker = dockerCreateContainer($inspectImage);
        logger(MAINTENANCE_LOG, 'dockerCreateContainer() ' . json_encode($docker, JSON_UNESCAPED_SLASHES));
        logger(MAINTENANCE_LOG, 'dockerCreateContainer() <-');

        if (strlen($docker['Id']) == 64) {
            $this->startMaintenance();
        }

        logger(MAINTENANCE_LOG, '$maintenance->createMaintenance() <-');
    }
}
