#!/usr/bin/python

import subprocess
import logging

from logsetting import getHandler

# create logger
logger = logging.getLogger('DICOMHandler')
logger.setLevel(logging.INFO)

#########################################################
#
"""
Helper classes to handle DICOM commands
"""
#
#########################################################

class DICOMCommand(object):
    """helper class to run dcmtk's executables
    """

    def __init__(self, running = False):
        self.running = running

    def __del__(self):
        self.stop()

    def start(self, cmd, args):
        if(self.running):
            self.stop(cmd)
        self.cmd = cmd
        self.args = args

        # start the cmd!
        self.running = True
        proc = subprocess.Popen(self.cmd + ' ' + self.args, stdin=None, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, shell=True)

    def stop(self, cmd):
        # dummy function, not kill process
        self.cmd = cmd
        self.running = False


class DICOMListener(DICOMCommand):
    """helper class to run dcmtk's storescp as listener
    """

    def __init__(self):
        super(DICOMListener,self).__init__()

    def __del__(self):
        super(DICOMListener,self).__del__()

    def start(self, incomingDir, onReceptionCallback, \
      storeSCPExecutable, storeSCPport, studyTimeout, \
      dcmqrSCPExecutable, dcmqrSCPConfigFile):
        self.incomingDir = incomingDir
        self.onReceptionCallback = onReceptionCallback
        self.storeSCPExecutable = storeSCPExecutable
        self.storeSCPport = storeSCPport
        self.studyTimeout = studyTimeout #seconds
        self.dcmqrSCPExecutable = dcmqrSCPExecutable
        self.dcmqrSCPConfigFile = dcmqrSCPConfigFile
        # start the server!
        storeSCP_args = str(self.storeSCPport) + ' --eostudy-timeout ' + str(self.studyTimeout) \
            + ' --output-directory ' + self.incomingDir \
            + ' --sort-on-study-uid  \'\'' \
            + ' --exec-on-eostudy ' + self.onReceptionCallback
        dcmqrSCP_args = '--config ' + self.dcmqrSCPConfigFile
        storeSCP_retcode = super(DICOMListener,self).start(self.storeSCPExecutable, storeSCP_args)
        dcmqrSCP_retcode = super(DICOMListener,self).start(self.dcmqrSCPExecutable, dcmqrSCP_args)
        # set up logger
        logger.addHandler(getHandler(self.incomingDir.strip()))
        logger.info("Started storeSCP with these args: %s" % storeSCP_args)
        logger.info("Started dcmqrSCP with these args: %s" % dcmqrSCP_args)
        return storeSCP_retcode and dcmqrSCP_retcode

    def stop(self, storeSCPExecutable='storescp'):
        # dummy function, not kill process
        self.storeSCPExecutable = storeSCPExecutable
        super(DICOMListener,self).stop(self.storeSCPExecutable)

