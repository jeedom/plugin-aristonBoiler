# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import sys
import os
import time
import traceback
import signal
import json
import argparse

try:
    from jeedom.jeedom import *
except ImportError as e:
    print("Error: importing module jeedom.jeedom" + str(e))
    sys.exit(1)



from ariston_boiler_control import AristonBoilerControl, OperationMode, HPState

ariston_conn = None




def get_boiler_infos(email, password, eqId):
    results = {}
    results['FUNC'] = 'getDatas'
    results['eqId'] = eqId
    results['data'] = {}
    global ariston_conn
    try:
        if ariston_conn is None:
            raise Exception("Connexion Ariston non initialisée")
        infos = {
            "current_temperature": ariston_conn.get_current_temperature(),
            "target_temperature": ariston_conn.get_target_temperature(),
            "operation_mode": str(ariston_conn.get_operation_mode()),
            "hpState": ariston_conn.get_hp_state(),
            "boostMode": ariston_conn.get_boost()
        }
        results['data'] = infos
        return results
    except Exception as e:
        logging.error(f"Erreur récupération infos chaudière : {e}")
        return {"error": str(e)}

def read_socket():
    global JEEDOM_SOCKET_MESSAGE
    global ret
    if not JEEDOM_SOCKET_MESSAGE.empty():
        logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
        message = json.loads(JEEDOM_SOCKET_MESSAGE.get())
        if message['apikey'] != _apikey:
            logging.error("Invalid apikey from socket: %s", message)
            return
        try:
            if message['action'] == 'getDatas':
                ret = get_boiler_infos(_email, _password, message['eqId'])
                jeedom_com.send_change_immediate(ret)
        except Exception as e:
            logging.error('Send command to demon error: %s', e)


def listen():
    jeedom_socket.open()
    try:
        while 1:
            time.sleep(0.5)
            read_socket()
    except KeyboardInterrupt:
        shutdown()


def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting...", int(signum))
    shutdown()


def shutdown():
    logging.debug("Shutdown")
    logging.debug("Removing PID file %s", _pidfile)
    try:
        os.remove(_pidfile)
    except Exception as e:
        logging.warning('Error removing PID file: %s', e)
    try:
        jeedom_socket.close()
    except Exception as e:
        logging.warning('Error closing socket: %s', e)
    # try:  # if you need jeedom_serial
    #     my_jeedom_serial.close()
    # except Exception as e:
    #     logging.warning('Error closing serial: %s', e)
    logging.debug("Exit 0")
    sys.stdout.flush()
    os._exit(0)


_log_level = "error"
_socket_port = 57130
_socket_host = 'localhost'
_device = 'auto'
_pidfile = '/tmp/demond.pid'
_apikey = ''
_callback = ''
_cycle = 0.3

parser = argparse.ArgumentParser(description='Daemon for Jeedom aristonBoiler')
parser.add_argument("--device", help="Device", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument("--socketport", help="Port for socket server", type=int)
parser.add_argument("--sockethost", help="Sockethost for server", type=str)
parser.add_argument("--email", help="Email for boiler control", type=str)
parser.add_argument("--password", help="Password for boiler control", type=str)
args = parser.parse_args()

if args.device:
    _device = args.device
if args.loglevel:
    _log_level = args.loglevel
if args.callback:
    _callback = args.callback
if args.apikey:
    _apikey = args.apikey
if args.pid:
    _pidfile = args.pid
if args.socketport:
    _socket_port = int(args.socketport)
if args.sockethost:
    _socket_host = args.sockethost

if args.email:
    _email = args.email
if args.password:
    _password = args.password

def init_ariston_connexion():
    global ariston_conn
    try:
        ariston_conn = AristonBoilerControl(_email, _password, quiet_login=True)
        ariston_conn.login()
        logging.info("Connexion Ariston initialisée avec succès.")
    except Exception as e:
        logging.error(f"Erreur lors de l'initialisation de la connexion Ariston : {e}")
        ariston_conn = None


_socket_port = int(_socket_port)

jeedom_utils.set_log_level(_log_level)

logging.info('Start demond aristonBoiler')
logging.info('Log level: %s', _log_level)
logging.info('Socket port: %s', _socket_port)
logging.info('Socket host: %s', _socket_host)
logging.info('PID file: %s', _pidfile)
logging.info('Apikey: %s', _apikey)
logging.info('Device: %s', _device)
logging.info('Callback: %s', _callback)


signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedom_com = jeedom_com(apikey=_apikey, url=_callback)
    if not jeedom_com.test():
        logging.error('Network communication issues. Please fixe your Jeedom network configuration.')
        shutdown()
    jeedom_socket = jeedom_socket(port=_socket_port, address=_socket_host)
    init_ariston_connexion()
    listen()
except Exception as e:
    logging.error('Fatal error: %s', e)
    logging.info(traceback.format_exc())
    shutdown()
