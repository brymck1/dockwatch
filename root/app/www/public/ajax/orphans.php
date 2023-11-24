<?php

/*
----------------------------------
 ------  Created: 112223   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $images     = json_decode(dockerGetOrphanContainers(), true);
    $volumes    = json_decode(dockerGetOrphanVolumes(), true);

    ?>
    <div class="container-fluid pt-4 px-4 mb-5">
        <div class="bg-secondary rounded h-100 p-4">
            <h4 class="mt-3 mb-0">Images</h4>
            <span style="small-text text-muted">docker images -f dangling=true</span>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input" onclick="$('.orphanImages-check').prop('checked', $(this).prop('checked'));"></th>
                            <th scope="col">ID</th>
                            <th scope="col">Created</th>
                            <th scope="col">Repository</th>
                            <th scope="col">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($images as $image) {
                            ?>
                            <tr id="image-<?= $image['ID'] ?>">
                                <th scope="row"><input id="orphanImage-<?= $image['ID'] ?>" type="checkbox" class="form-check-input orphanImages-check orphan"></th>
                                <td><?= $image['ID'] ?></td>
                                <td><?= $image['CreatedSince'] ?></td>
                                <td><?= $image['Repository'] ?></td>
                                <td><?= $image['Size'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3 mb-0">Volumes</h4>
            <span style="small-text text-muted">docker volume ls -qf dangling=true</span>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input" onclick="$('.orphanVolumes-check').prop('checked', $(this).prop('checked'));"></th>
                            <th scope="col">Name</th>
                            <th scope="col">Mount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($volumes as $volume) {
                            ?>
                            <tr id="volume-<?= $volume['Name'] ?>">
                                <th scope="row"><input id="orphanVolume-<?= $volume['Name'] ?>" type="checkbox" class="form-check-input orphanVolumes-check orphan"></th>
                                <td><?= $volume['Name'] ?></td>
                                <td><?= $volume['Mountpoint'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                With selected: 
                                <select id="massOrphanTrigger" class="form-control d-inline-block w-50">
                                    <option value="0">-- Select option --</option>
                                    <option value="1">Remove</option>
                                    <option value="2">Prune</option>
                                </select>
                                <button type="button" class="btn btn-outline-info" onclick="removeOrphans()">Apply</button>
                            </td>
                            <td>&nbsp;</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'removeOrphans') {
    switch ($_POST['action']) {
        case 'remove':
            if ($_POST['type'] == 'image') {
                $remove = dockerRemoveImage($_POST['orphan']);
                if (stripos($remove, 'error') !== false) {
                    echo $remove;
                }
            }
            if ($_POST['type'] == 'volume') {
                $remove = dockerRemoveVolume($_POST['orphan']);
                if (stripos($remove, 'error') !== false) {
                    echo $remove;
                }
            }
            break;
        case 'prune':
            if ($_POST['type'] == 'image') {
                $prune = dockerPruneImage($_POST['orphan']);
                if (stripos($prune, 'error') !== false) {
                    echo $prune;
                }
            }
            if ($_POST['type'] == 'volume') {
                $prune = dockerPruneVolume($_POST['orphan']);
                if (stripos($prune, 'error') !== false) {
                    echo $prune;
                }
            }
            break;
    }
}
