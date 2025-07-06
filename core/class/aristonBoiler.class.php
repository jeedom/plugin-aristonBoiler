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

    public static function cronInterval($interval) {
      $valueCronTemp = config::byKey('cronchoice', 'aristonBoiler', 5);
      $valueCron = trim($valueCronTemp);
      if(!is_numeric($valueCron)){
          if($valueCron != 'Daily' || $valueCron != 'daily'){
            throw new Exception(__('Veuillez vérifier la configuration du cron, valeur non attendue', __FILE__));
          }else{
            $valueCron = 'Daily';
          }  
      }
      if ($valueCron == $interval) self::getDatas();
  }

  

  public static function cron() {
    self::cronInterval(1);
  }
  


  public static function cron5() {
    self::cronInterval(5);
  }



  public static function cron10() {
    self::cronInterval(10);
  }



  public static function cron15() {
    self::cronInterval(15);
  }



  public static function cron30() {
    self::cronInterval(30);
  }



  public static function cronHourly() {
    self::cronInterval(60);
  }



  public static function cronDaily() {
    self::cronInterval('Daily');
  }

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
        $cmd .= ' --sockethost 127.0.0.1';
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/aristonBoiler/core/php/jeeAristonBoiler.php';
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        $cmd .= ' --email ' . config::byKey('email', __CLASS__, '');
        $cmd .= ' --password ' . config::byKey('password', __CLASS__, '');
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

    public static function socketConnection($value) {

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $timeout = array('sec' => 180, 'usec' => 0);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout);
        $result = socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'aristonBoiler', 57130));
        if ($result === false) {
            log::add(__CLASS__,'error', "socket_connect() failed");
            socket_close($socket);
            return;
        }
        socket_write($socket, $value, strlen($value));
        socket_close($socket);
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
      $getCurrentTemp->setName(__('Température actuelle', __FILE__));
      $getCurrentTemp->setEqLogic_id($this->getId());
      $getCurrentTemp->setLogicalId('getCurrentTemp');
      $getCurrentTemp->setType('info');
      $getCurrentTemp->setSubType('numeric');
      $getCurrentTemp->setIsHistorized(0);
      $getCurrentTemp->save();  
    }
    $getCurrentTemp->setName(__('Température actuelle', __FILE__));
    $getCurrentTemp->setUnite('°C');
    $getCurrentTemp->save();
    $getTargetTemp = $this->getCmd(null, 'getTargetTemp');
    if (!is_object($getTargetTemp)) {
      $getTargetTemp = new aristonBoilerCmd();
      $getTargetTemp->setName(__('Température cible', __FILE__));
      $getTargetTemp->setEqLogic_id($this->getId());
      $getTargetTemp->setLogicalId('getTargetTemp');
      $getTargetTemp->setType('info');
      $getTargetTemp->setSubType('numeric');
      $getTargetTemp->setIsHistorized(0);
      $getTargetTemp->save(); 
    }
    $getTargetTemp->setName(__('Température cible', __FILE__));
    $getTargetTemp->setUnite('°C');
    $getTargetTemp->save();
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
      $getOperationMode->setName(__('Mode de fonctionnement', __FILE__));
      $getOperationMode->setEqLogic_id($this->getId());
      $getOperationMode->setLogicalId('getOperationMode');
      $getOperationMode->setType('info');
      $getOperationMode->setSubType('string');
      $getOperationMode->setIsHistorized(0);
      $getOperationMode->save();
    }
    $getOperationMode->setName(__('Mode de fonctionnement', __FILE__));
    $getOperationMode->save();
    $getOperationModeId = $getOperationMode->getId();

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
    $setOperationMode->setValue($getOperationModeId);
    $setOperationMode->save();  

    $getBoostMode = $this->getCmd(null, 'getBoostMode');
    if (!is_object($getBoostMode)) {
      $getBoostMode = new aristonBoilerCmd();
      $getBoostMode->setName(__('Mode Boost', __FILE__));
      $getBoostMode->setEqLogic_id($this->getId());
      $getBoostMode->setLogicalId('getBoostMode');
      $getBoostMode->setType('info');
      $getBoostMode->setSubType('binary');
      $getBoostMode->setIsHistorized(0);
      $getBoostMode->save();
    }
    $getBoostMode->setName(__('Mode Boost', __FILE__));
    $getBoostMode->save();
    $getBoostModeId = $getBoostMode->getId();

    $setBoostModeOn = $this->getCmd(null, 'setBoostModeOn');
    if (!is_object($setBoostModeOn)) {
      $setBoostModeOn = new aristonBoilerCmd();
      $setBoostModeOn->setName(__('Activation du mode Boost', __FILE__));
      $setBoostModeOn->setEqLogic_id($this->getId());
      $setBoostModeOn->setLogicalId('setBoostModeOn');
      $setBoostModeOn->setType('action');
      $setBoostModeOn->setSubType('other');
      $setBoostModeOn->setIsHistorized(0);
      $setBoostModeOn->save();
    }

    $setBoostModeOff = $this->getCmd(null, 'setBoostModeOff');
    if (!is_object($setBoostModeOff)) {
      $setBoostModeOff = new aristonBoilerCmd();
      $setBoostModeOff->setName(__('Désactivation du mode Boost', __FILE__));
      $setBoostModeOff->setEqLogic_id($this->getId());
      $setBoostModeOff->setLogicalId('setBoostModeOff');
      $setBoostModeOff->setType('action');
      $setBoostModeOff->setSubType('other');
      $setBoostModeOff->setIsHistorized(0);
      $setBoostModeOff->save();
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


    public static function getDatas() {
       $email = config::byKey('email', 'aristonBoiler', '');
       $password = config::byKey('password', 'aristonBoiler', '');
        if (empty($email) || empty($password)) {
            throw new Exception(__('Veuillez renseigner votre email et mot de passe dans la configuration du plugin', __FILE__));
        }
        $eqLogics = eqLogic::byType('aristonBoiler');
        foreach ($eqLogics as $eqLogic) {
            $value = json_encode(array(
              'apikey' => jeedom::getApiKey('aristonBoiler'),
              'action' => 'getDatas',
              'eqId' => $eqLogic->getId()
            ));
            self::socketConnection($value);
        }
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

       $associatedOperationMode = array(
        1 => 'OperationMode.GREEN',
        2 => 'OperationMode.COMFORT',
        3 => 'OperationMode.FAST',
        4 => 'OperationMode.AUTO',
        5 => 'OperationMode.HCHP'
    );


    $logicalCmd = $this->getLogicalId();
    $eqlogic = $this->getEqLogic();

    switch ($logicalCmd) {
      case 'setTargetTemp':
        $value = intval($_options['slider']);
        if ($value < 30 || $value > 80) {
          throw new Exception(__('La température cible doit être comprise entre 30 et 80°C', __FILE__));
        }
        $data = array(
          'action' => 'setTargetTemp',
          'eqId' => $eqlogic->getId(),
          'value' => floatVal($value)
        );
        break;

      case 'setOperationMode':
        $value = $_options['select'];
        $data = array(
          'action' => 'setOperationMode',
          'eqId' => $eqlogic->getId(),
          'value' => $associatedOperationMode[intVal($value)]
        );
        break;

      case 'setBoostModeOn':
        $data = array(
          'action' => 'setBoostMode',
          'eqId' => $eqlogic->getId(),
          'value' => true
        );
      case 'setBoostModeOff':
        $data = array(
          'action' => 'setBoostMode',
          'eqId' => $eqlogic->getId(),
          'value' => false
        );
        break;

      default:
        throw new Exception(__('Commande non reconnue', __FILE__));
    }

    $value = json_encode($data);
    aristonBoiler::socketConnection($value);
    log::add('aristonBoiler', 'debug', '┌─▶︎ Exécution de la commande : ' . $this->getName() );
  }

  /*     * **********************Getteur Setteur*************************** */
}
