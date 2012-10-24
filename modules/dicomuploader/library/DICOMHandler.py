#!/usr/bin/python

import subprocess

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
        #print ("Starting %s with " % cmd, args)
        self.running = True
        p = subprocess.Popen(self.cmd + ' ' + self.args, stdin=None, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, shell=True)
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
      storeSCPExecutable, port, studyTimeout):
        self.incomingDir = incomingDir
        self.onReceptionCallback = onReceptionCallback
        self.storeSCPExecutable = storeSCPExecutable
        self.port = port
        self.studyTimeout = studyTimeout #seconds
        # start the server!
        args = str(self.port) + ' -ac --eostudy-timeout ' + str(self.studyTimeout) \
            + ' --output-directory ' + self.incomingDir \
            + ' --sort-on-study-uid  \'\'' \
            + ' --exec-on-eostudy ' + self.onReceptionCallback
        #print("starting DICOM listener")
        retcode = super(DICOMListener,self).start(self.storeSCPExecutable, args)
        return retcode

    def stop(self, storeSCPExecutable='storescp'):
        # dummy function, not kill process
        self.storeSCPExecutable = storeSCPExecutable
        super(DICOMListener,self).stop(self.storeSCPExecutable)


