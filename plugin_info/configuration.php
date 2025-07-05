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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
     <div class="form-group">
      <label class="col-md-4 control-label">{{SocketPort}}</label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="socketport" placeholder="57130"/>
      </div>
    </div> 
    <div class="form-group">
      <label class="col-md-4 control-label">{{Choix Cron}}</label>
      <div class="col-md-4">
        <select class="configKey form-control"  data-l1key="cronchoice"> 
          <option value="1">{{1 minute}}</option>
          <option value="5">{{5 minutes}}</option>
          <option value="10">{{10 minutes}}</option>
          <option value="15">{{15 minutes}}</option>
          <option value="30">{{30 minutes}}</option>
          <option value="60">{{1 heure}}</option>
          <option value="daily">{{Tous les jours}}</option>
        </select>
        <!-- <input class="configKey form-control" data-l1key="cronchoice"  placeholder="Choix cron (1,5,10,15,30,60 ou Daily)"/> -->
      </div>
    </div>
  </fieldset>
</form>
