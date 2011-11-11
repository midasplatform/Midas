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
def handleMidasResponse(response):
  """
  Handle response
  """
  if response['action'] == 'wait':
    print "Wait"
    time.sleep(120)
  elif response['action'] == 'process':
    params = json.loads(response['params'])
    script = response['script']

    #Create processing folder
    unique_name = str(uuid.uuid4())
    pathProcessingFolder = sys.path[0]+'/tmp/'+unique_name
    os.mkdir(pathProcessingFolder)
    os.mkdir(pathProcessingFolder+'/script')
    os.mkdir(pathProcessingFolder+'/results')

    #Create Script file
    try: scriptFile = open(pathProcessingFolder+'/script/script.py', "w")
    except Exception, e: raise
    scriptFile.write(script)
    scriptFile.close()

    #Create Params file
    try: scriptFile = open(pathProcessingFolder+'/results/parameters.txt', "w")
    except Exception, e: raise
    scriptFile.write(response['params'])
    scriptFile.close()

    inputFiles = params['input']
    cfg = loadConfig('config.cfg')
    cfginternal = loadConfig('config.internal.cfg')
    url = cfg['url']
    interfaceMidas = apiMidas.Communicator (url)

    if inputFiles:
      print "Download Data"
      for file in inputFiles:
        interfaceMidas.downloadItem(file, pathProcessingFolder+'/script', cfginternal['token'])

    print "Run script"
    os.chdir(pathProcessingFolder+'/script/')
    cmd = sys.executable+" "+pathProcessingFolder+'/script/script.py'
    p = Popen(cmd, shell=True, stdin=PIPE, stdout=PIPE, stderr=STDOUT, close_fds=False)
    p.wait()
    stdout = p.stdout.read()
    os.chdir(sys.path[0])

    #Create Log files
    try: scriptFile = open(pathProcessingFolder+'/results/log.txt', "w")
    except Exception, e: raise
    scriptFile.write(stdout)
    scriptFile.close()

    outputFiles = params['output']
    if outputFiles:
      for file in outputFiles:
        if os.path.exists(pathProcessingFolder+'/script/'+file):
          os.rename(pathProcessingFolder+'/script/'+file, pathProcessingFolder+'/results/'+file)

    zipdir(pathProcessingFolder+'/results', pathProcessingFolder+'/results.zip')
    print "Sending results"
    sendResults(pathProcessingFolder+'/results.zip')
    shutil.rmtree(pathProcessingFolder)
  else:
    print "Error, Unable to find command"
    return False
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
  while True:
    os.chdir(sys.path[0])

    registered = registerServer()

    # Create tmp directory
    if os.path.exists(sys.path[0]+'/tmp') == False:
      os.mkdir('tmp')

    if registered == True:
      response = keepAliveServer()
      if response != False:
        handleMidasResponse(response)
      else:
        time.sleep(120)
    else:
      time.sleep(120)


