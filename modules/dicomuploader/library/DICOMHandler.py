import os
import signal

#########################################################
#
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
            self.stop()
        self.cmd = cmd
        self.args = args

        # start the cmd!
        print ("Starting %s with " % cmd, args)
        self.running = True
        os.system(self.cmd + ' ' + self.args)


    def stop(self):
        for line in os.popen("ps xa"):
            fields = line.split()
            pid = fields[0]
            process = fields[4]
            if process.find(self.cmd) > 0:
                # Kill the Process. Change signal.SIGHUP to signal.SIGKILL if you like
                os.kill(int(pid), signal.SIGHUP)
                self.running = False
                break


class DICOMListener(DICOMCommand):
    """helper class to run dcmtk's storescp as listener
    """

    def __init__(self, incomingDir, onReceptionCallback, \
      storeSCPExecutable = 'storescp', port = 55555, studyTimeout = 30):
        self.incomingDir = incomingDir
        if not os.path.exists(self.incomingDir):
            os.mkdir(self.incomingDir)
        self.onReceptionCallback = onReceptionCallback
        self.storeSCPExecutable = storeSCPExecutable
        self.port = port
        self.studyTimeout = studyTimeout #seconds
        super(DICOMListener,self).__init__()


    def __del__(self):
        super(DICOMListener,self).__del__()

    def start(self):
        # start the server!
        args = str(self.port) + ' --eostudy-timeout ' + str(self.studyTimeout) \
            +' --output-directory ' + self.incomingDir \
            + ' --sort-on-study-uid  \'\'' \
            + ' --exec-on-eostudy ' + self.onReceptionCallback
        print("starting DICOM listener")
        super(DICOMListener,self).start(self.storeSCPExecutable, args)
