#! /usr/bin/python
import re
import os
import sys
import time
import pydas.communicator as apiMidas
import pydas.exceptions as pydasException
import uuid
import json
import shutil
from zipfile import ZipFile, ZIP_DEFLATED
from subprocess import Popen, PIPE, STDOUT
from contextlib import closing

# Load configuration file
def loadConfig(filename):
   try: configfile = open(filename, "r")
   except Exception, e: raise
   try: configtext = configfile.read()
   except Exception, e: raise
   pattern = re.compile("\\n([\w_]+)[\t ]*([\w: \\\/~.-]+)")
   # Find all matches to this pattern in the text of the config file
   tuples = re.findall(pattern, configtext)
   # Create a new dictionary and fill it: for every tuple (key, value) in
   # the 'tuples' list, set ret[key] to value
   ret = dict()
   for x in tuples: ret[x[0]] = x[1]
   # Return the fully-loaded dictionary object
   return ret

# Set internal configuration
def setInternalConfig(email, apikey, token):
   try: configfile = open('config.internal.cfg', "w")
   except Exception, e: raise

   configfile.write("\nemail "+email)
   configfile.write("\napikey "+apikey)
   configfile.write("\ntoken "+token)
   configfile.close()
   return

# Register a server to Midas
def registerServer():
    """
    Register Server
    """
    cfg = loadConfig('config.cfg')
    if os.path.exists('config.internal.cfg') == False:
      setInternalConfig('undefined', 'undefined', 'undefined')
    cfginternal = loadConfig('config.internal.cfg')
    url = cfg['url']
    interfaceMidas = apiMidas.Communicator (url)

    parameters = dict()
    parameters['email'] = cfginternal['email']+'@foo.com'
    parameters['securitykey'] = cfg['securityKey']
    parameters['apikey'] = cfginternal['apikey']
    try: response = interfaceMidas.makeRequest('midas.remoteprocessing.registerserver', parameters)
    except pydasException.PydasException, e:
      parameters = dict()
      parameters['securitykey'] = cfg['securityKey']
      parameters['os'] = cfg['os']
      try: response = interfaceMidas.makeRequest('midas.remoteprocessing.registerserver', parameters)
      except pydasException.PydasException, e:
        print "Unable to Register. Please check the configuration."
        return False
    setInternalConfig(response['email'], response['apikey'], response['token'])
    print "Registered"
    return True

# Register a server to Midas
def keepAliveServer():
    """
    Keep Alive
    """
    cfg = loadConfig('config.cfg')
    cfginternal = loadConfig('config.internal.cfg')
    url = cfg['url']
    interfaceMidas = apiMidas.Communicator (url)

    parameters = dict()
    parameters['token'] = cfginternal['token']
    parameters['os'] = cfg['os']
    try: response = interfaceMidas.makeRequest('midas.remoteprocessing.keepaliveserver', parameters)
    except pydasException.PydasException, e:
      print "Keep aline failed"
      print e
      return False
    return response

# Send results to Midas
def sendResults(file):
    """
    Send Results
    """
    cfg = loadConfig('config.cfg')
    cfginternal = loadConfig('config.internal.cfg')
    url = cfg['url']
    interfaceMidas = apiMidas.Communicator (url)

    parameters = dict()
    parameters['token'] = cfginternal['token']
    try: response = interfaceMidas.makeRequest('midas.remoteprocessing.resultsserver', parameters, file)
    except pydasException.PydasException, e:
      print "Unable to send results"
      print e
      return False
    return response

# Handle Midas command
def process():
  cfg = loadConfig('config.cfg')
  cfginternal = loadConfig('config.internal.cfg')
  url = cfg['url']
  interfaceMidas = apiMidas.Communicator (url)
  #print "Download Data"
  #interfaceMidas.downloadItem(item, sys.path[0]+'/results/', cfginternal['token'])

  print "Run script"

  zipdir(sys.path[0]+'/results', sys.path[0]+'/results.zip')
  print "Sending results"
  sendResults(sys.path[0]+'/results.zip');
  return True

def zipdir(basedir, archivename):
  assert os.path.isdir(basedir)
  with closing(ZipFile(archivename, "w", ZIP_DEFLATED)) as z:
      for root, dirs, files in os.walk(basedir):
          #NOTE: ignore empty directories
          for fn in files:
              absfn = os.path.join(root, fn)
              zfn = absfn[len(basedir)+len(os.sep):] #XXX: relative path
              z.write(absfn, zfn)


# ------ Main --------
if __name__ == "__main__":
  #Set directory location
  os.chdir(sys.path[0])
  registered = registerServer()

  if registered == True:
    response = process()



