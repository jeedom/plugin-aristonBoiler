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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class aristonBoiler extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */
  
  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      // Cette function doit retourner des infos complémentataires sous la forme d'un
      // string contenant les infos formatées en HTML.
      return "les infos essentiel de mon plugin";
   }
   */

       public static function deamon_info(){
        $return = array();
        $return['log'] = 'aristonBoiler';
        $return['state'] = 'nok';

        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)){
            $pid = trim(file_get_contents($pid_file));
            if ($pid && function_exists('posix_getsid') && posix_getsid($pid)){
                $return['state'] = 'ok';
            }else{
                shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
            }
        }
        $return['launchable'] = 'ok';
        return $return;
    }

        public static function deamon_start(){
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok')
        {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $path = realpath(dirname(__FILE__) . '/../../resources/aristonBoilerd');
        $cmd = system::getCmdPython3(__CLASS__) .  "{$path}/aristonBoilerd.py";
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__, '57130');
        $cmd .= ' --retrydefault ' . config::byKey('retrydefault', __CLASS__, 'False');
        $cmd .= ' --timeoutretries ' . config::byKey('timeoutretries', __CLASS__, 0);
        $cmd .= ' --sockethost 127.0.0.1';
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/modbus/core/php/jeeModbus.php';
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        log::add(__CLASS__, 'info', 'Lancement démon ' . $cmd);
        $result = exec($cmd . ' >> ' . log::getPathToLog('aristonBoiler') . ' 2>&1 &');
        $i = 0;
        while ($i < 5){
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok')
            {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 5){
            log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__) , 'unableStartDeamon');
            return false;
        }
        message::removeAll(__CLASS__, 'unableStartDeamon');
        return true;
    }

    public static function deamon_stop(){
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)){
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }
        system::kill('aristonBoilerd.py');
        system::fuserk(config::byKey('socketport', 'aristonBoiler'));
        sleep(1);
    }



  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {

    $this->createCmds();
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }


  public function createCmds() {
   $readHPState = $this->getCmd(null, 'readHPState');
    if (!is_object($readHPState)) {
      $readHPState = new aristonBoilerCmd();
      $readHPState->setName(__('Lecture de l\'état de la pompe à chaleur', __FILE__));
      $readHPState->setEqLogic_id($this->getId());
      $readHPState->setLogicalId('readHPState');
      $readHPState->setType('info');
      $readHPState->setSubType('binary');
      $readHPState->setIsHistorized(0);
      $readHPState->save();
    }
    $getCurrentTemp = $this->getCmd(null, 'getCurrentTemp');
    if (!is_object($getCurrentTemp)) {
      $getCurrentTemp = new aristonBoilerCmd();
      $getCurrentTemp->setName(__('Lecture de la température actuelle', __FILE__));
      $getCurrentTemp->setEqLogic_id($this->getId());
      $getCurrentTemp->setLogicalId('getCurrentTemp');
      $getCurrentTemp->setType('info');
      $getCurrentTemp->setSubType('numeric');
      $getCurrentTemp->setIsHistorized(0);
      $getCurrentTemp->save();  
    }
    $getTargetTemp = $this->getCmd(null, 'getTargetTemp');
    if (!is_object($getTargetTemp)) {
      $getTargetTemp = new aristonBoilerCmd();
      $getTargetTemp->setName(__('Lecture de la température cible', __FILE__));
      $getTargetTemp->setEqLogic_id($this->getId());
      $getTargetTemp->setLogicalId('getTargetTemp');
      $getTargetTemp->setType('info');
      $getTargetTemp->setSubType('numeric');
      $getTargetTemp->setIsHistorized(0);
      $getTargetTemp->save(); 
    }
    $setTargetTemp = $this->getCmd(null, 'setTargetTemp');
    if (!is_object($setTargetTemp)) {
      $setTargetTemp = new aristonBoilerCmd();
      $setTargetTemp->setName(__('Réglage de la température cible', __FILE__));
      $setTargetTemp->setEqLogic_id($this->getId());
      $setTargetTemp->setLogicalId('setTargetTemp');
      $setTargetTemp->setType('action');
      $setTargetTemp->setSubType('slider');
      $setTargetTemp->setIsHistorized(0);
      $setTargetTemp->save(); 
    }
    $getOperationMode = $this->getCmd(null, 'getOperationMode');
    if (!is_object($getOperationMode)) {
      $getOperationMode = new aristonBoilerCmd();
      $getOperationMode->setName(__('Lecture du mode de fonctionnement', __FILE__));
      $getOperationMode->setEqLogic_id($this->getId());
      $getOperationMode->setLogicalId('getOperationMode');
      $getOperationMode->setType('info');
      $getOperationMode->setSubType('string');
      $getOperationMode->setIsHistorized(0);
      $getOperationMode->save();
    }
    $setOperationMode = $this->getCmd(null, 'setOperationMode');
    if (!is_object($setOperationMode)) {
      $setOperationMode = new aristonBoilerCmd();
      $setOperationMode->setName(__('Réglage du mode de fonctionnement', __FILE__));
      $setOperationMode->setEqLogic_id($this->getId());
      $setOperationMode->setLogicalId('setOperationMode');
      $setOperationMode->setType('action');
      $setOperationMode->setSubType('select');
      $listValue = $this->generateStageListValue();
      $setOperationMode->setConfiguration('listValue', $listValue);
      $setOperationMode->setIsHistorized(0);
      $setOperationMode->save();    
    }
    $getBoostMode = $this->getCmd(null, 'getBoostMode');
    if (!is_object($getBoostMode)) {
      $getBoostMode = new aristonBoilerCmd();
      $getBoostMode->setName(__('Lecture du mode Boost', __FILE__));
      $getBoostMode->setEqLogic_id($this->getId());
      $getBoostMode->setLogicalId('getBoostMode');
      $getBoostMode->setType('info');
      $getBoostMode->setSubType('binary');
      $getBoostMode->setIsHistorized(0);
      $getBoostMode->save();
    }
    $setBoostMode = $this->getCmd(null, 'setBoostMode');
    if (!is_object($setBoostMode)) {
      $setBoostMode = new aristonBoilerCmd();
      $setBoostMode->setName(__('Activation du mode Boost', __FILE__));
      $setBoostMode->setEqLogic_id($this->getId());
      $setBoostMode->setLogicalId('setBoostMode');
      $setBoostMode->setType('action');
      $setBoostMode->setSubType('other');
      $setBoostMode->setIsHistorized(0);
      $setBoostMode->save();
    }
  }

   public function generateStageListValue() {
        $listValue = "1|Green";
        $listValue .= ";2|Comfort";
        $listValue .= ";3|Fast";
        $listValue .= ";4|Auto";
        $listValue .= ";5|HCHP";
        return $listValue;
    }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*     * **********************Getteur Setteur*************************** */
}

class aristonBoilerCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
  }

  /*     * **********************Getteur Setteur*************************** */
}
