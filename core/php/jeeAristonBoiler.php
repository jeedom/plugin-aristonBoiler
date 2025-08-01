<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once __DIR__ . '/../../../../core/php/core.inc.php';


/*
 * Non obligatoire mais peut être utilisé si vous voulez charger en même temps que votre
 * plugin des librairies externes (ne pas oublier d'adapter plugin_info/info.xml).
 *
 *
*/
if (!jeedom::apiAccess(init('apikey'), 'aristonBoiler')) {
    echo __('Vous n\'êtes pas autorisé à effectuer cette action', __FILE__);
    die();
}




$result = json_decode(file_get_contents("php://input"), true);
log::add('aristonBoiler', 'debug', '┌─▶︎ CallBack from AristonBoiler: ' . json_encode($result) . ' ◀︎───────────');

if (!is_array($result)) {
    die();
}



if($result['FUNC'] == 'getDatas') {
    $associatedOperationMode = array(
        'OperationMode.GREEN' => 1,
        'OperationMode.COMFORT' => 2,
        'OperationMode.FAST' => 3,
        'OperationMode.AUTO' => 4,
        'OperationMode.HCHP' => 5
    );
     $associatedGetOperationMode = array(
        'OperationMode.GREEN' => 'Green',
        'OperationMode.COMFORT' => 'Comfort',
        'OperationMode.FAST' => 'Fast',
        'OperationMode.AUTO' => 'Auto',
        'OperationMode.HCHP' => 'HCHP'
    );
    $eqId = $result['eqId'];
    $data = $result['data'];
    log::add('aristonBoiler', 'debug', '└─▶︎ Event sur Cmds : ' . json_encode($data) . ' ◀︎───────────');
    
    $eqLogic = aristonBoiler::byId($eqId);
    if (!is_object($eqLogic)) {
        log::add('aristonBoiler', 'error', 'Équipement introuvable avec l\'ID: ' . $eqId);
        return;
    }

    $cmds = $eqLogic->getCmd('info');
    foreach ($cmds as $cmd) {
        switch ($cmd->getLogicalId()) {
            case 'getCurrentTemp':
                $cmd->event($data['current_temperature']);
                break;
            case 'getTargetTemp':
                $cmd->event($data['target_temperature']);
                break;
            case 'getOperationMode':
                $cmd->event($associatedGetOperationMode[$data['operation_mode']]);
                break;
            // case 'setOperationMode':
            //     $cmd->execCmd($associatedOperationMode[$data['operation_mode']]);
            //     break;
            case 'readHPState':
                $cmd->event($data['hpState'] == 2 ? 1 : 0);
                break;
            case 'getBoostMode':
                $cmd->event($data['boostMode'] ? 1 : 0);
                break;
            default:
                // log::add('aristonBoiler', 'debug', 'Commande non gérée:' . $cmd->getLogicalId());
                continue 2; 
        }
        log::add('aristonBoiler', 'debug', 'Commande mise à jour: ' . $cmd->getName() . ' avec la valeur: ' . $cmd->execCmd());
    }

}
