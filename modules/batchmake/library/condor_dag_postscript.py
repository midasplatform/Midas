#! /usr/bin/python
import os
import sys
import pydas.drivers
import pydas.exceptions
import pydas.core as core

# Load configuration file
def loadConfig(filename):
   try:
     configfile = open(filename, "r")
     ret = dict()
     for x in configfile:
       x = x.strip()
       if not x: continue
       cols = x.split()
       ret[cols[0]] = cols[1]
     return ret
   except Exception, e: raise



def openLog(logpath):
  log = open(os.path.join(outputDir,'postscript'+jobidNum+'.log'),'w')
  log.write('Condor Post Script log\n\nsys.argv:\n\n')
  log.write('\t'.join(sys.argv))
  return log

def logConfig(log, cfgParams):
  log.write('\n\nConfig Params:\n\n')
  log.write('\n'.join(['\t'.join((k,v)) for (k,v) in cfgParams.iteritems()])) 





if __name__ == "__main__":
  (scriptName, outputDir, taskId, dagName, jobId, jobName, returnCode) = sys.argv

  jobidNum = jobName[3:]
  # get config params
  cfgParams = loadConfig('config.cfg')

  log = openLog(os.path.join(outputDir,'postscript'+jobidNum+'.log'))
  logConfig(log, cfgParams)

  # open connection to midas
  interfaceMidas = core.Communicator (cfgParams['url'])
  token = interfaceMidas.login_with_api_key(cfgParams['email'], cfgParams['apikey'])
 
  log.write("\n\nLogged into midas, got token: "+token+"\n\n")

  dagfilename = dagName + ".dagjob"
  dagmanoutfilename = dagfilename + ".dagman.out"
  log.write("\n\nCalling add condor dag with params:"+token+" "+taskId+" "+dagfilename+" "+ dagmanoutfilename+"\n\n")
  # add the condor_dag
  dagResponse = interfaceMidas.add_condor_dag(token, taskId, dagfilename, dagmanoutfilename)
  log.write("\n\nAdded a Condor Dag with response:"+str(dagResponse)+"\n\n")

  log.close()













