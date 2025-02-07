<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $dependencyFile = updateContainerDependencies($processList);
    $ports = $networks = [];
    $running = $stopped = $memory = $cpu = $network = $size = $updated = $outdated = $healthy = $unhealthy = $unknownhealth = 0;

    foreach ($processList as $process) {
        $size += bytesFromString($process['size']);

        if (str_contains($process['Status'], 'healthy')) {
            $healthy++;
        } elseif (str_contains($process['Status'], 'unhealthy')) {
            $unhealthy++;
        } elseif (!str_contains($process['Status'], 'health')) {
            $unknownhealth++;
        }

        if ($process['State'] == 'running') {
            $running++;
        } else {
            $stopped++;
        }

        //-- GET UPDATES
        if ($pullsFile) {
            foreach ($pullsFile as $hash => $pull) {
                if (md5($process['Names']) == $hash) {
                    if ($pull['regctlDigest'] == $pull['imageDigest']) {
                        $updated++;
                    } else {
                        $outdated++;
                    }
                    break;
                }
            }
        }

        //-- GET USED NETWORKS
        if ($process['inspect'][0]['NetworkSettings']['Networks']) {
            $networkKeys = array_keys($process['inspect'][0]['NetworkSettings']['Networks']);
            foreach ($networkKeys as $networkKey) {
                $networks[$networkKey]++;
            }
        } else {
            $containerNetwork = $process['inspect'][0]['HostConfig']['NetworkMode'];
            if (str_contains($containerNetwork, ':')) {
                list($null, $containerId) = explode(':', $containerNetwork);
                $containerNetwork = 'container:' . findContainerFromId($containerId);
            }

            $networks[$containerNetwork]++;
        }

        //-- GET USED PORTS
        if ($process['inspect'][0]['HostConfig']['PortBindings']) {
            foreach ($process['inspect'][0]['HostConfig']['PortBindings'] as $internalBind => $portBinds) {
                foreach ($portBinds as $portBind) {
                    if ($portBind['HostPort']) {
                        $ports[$process['Names']][] = $portBind['HostPort'];
                    }
                }
            }
        }

        //-- GET MEMORY UAGE
        $memory += floatval(str_replace('%', '', $process['stats']['MemPerc']));

        //-- GET CPU USAGE
        $cpu += floatval(str_replace('%', '', $process['stats']['CPUPerc']));

        //-- GET NETWORK USAGE
        list($netUsed, $netAllowed) = explode(' / ', $process['stats']['NetIO']);
        $network += bytesFromString($netUsed);
    }

    $cpu = $cpu > 0 ? number_format((($running + $stopped) * 100) / $cpu, 2) : 0;
    if (intval($settingsFile['global']['cpuAmount']) > 0) {
        $cpuActual = number_format(($cpu / intval($settingsFile['global']['cpuAmount'])), 2);
    }

    ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Status</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        Running: <?= $running ?><br>
                        Stopped: <?= $stopped ?><br>
                        Total: <?= ($running + $stopped) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Health</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        Healthy: <?= $healthy ?><br>
                        Unhealthy: <?= $unhealthy ?><br>
                        Unknown: <?= $unknownhealth ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Updates</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        Up to date: <?= $updated ?><br>
                        Outdated: <?= $outdated ?><br>
                        Unchecked: <?= (($running + $stopped) - ($updated + $outdated)) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Usage</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        Disk:  <?= byteConversion($size) ?><br>
                        CPU: <?= $cpu ?>%<?= ($cpuActual ? ' (' . $cpuActual . '%)' : '') ?><br>
                        Memory: <?= $memory ?>%<br>
                        Network I/O: <?= byteConversion($network) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Network</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        <?php
                        $networkList = '';
                        foreach ($networks as $networkName => $networkCount) {
                            $networkList .= ($networkList ? '<br>' : '') . truncateMiddle($networkName, 30) . ': ' . $networkCount;
                        }
                        echo '<div style="max-height: 250px; overflow: auto;">' . $networkList . '</div>';
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-2">
                        <h3>Ports</h3>
                    </div>
                    <div class="col-sm-12 col-lg-10">
                        <?php
                        $portArray = [];
                        $portList = '';
                        if ($ports) {
                            foreach ($ports as $container => $containerPorts) {
                                foreach ($containerPorts as $containerPort) {
                                    $portArray[$containerPort] = $container;
                                }
                            }
                            ksort($portArray);
                            $portArray = formatPortRanges($portArray);
                            
                            if ($portArray) {
                                $portList = '<div style="max-height: 250px; overflow: auto;">';

                                foreach ($portArray as $port => $container) {
                                    $portList .= '<div class="row flex-nowrap p-0 m-0">';
                                    $portList .= '  <div class="col text-end">' . $port . '</div>';
                                    $portList .= '  <div class="col text-end" title="' . $container . '">' . truncateMiddle($container, 14) . '</div>';
                                    $portList .= '</div>';    
                                }

                                $portList .= '</div>';
                            }
                        }
                        echo $portList;
                    ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    displayTimeTracking($loadTimes);
}