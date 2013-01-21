import os
import logging
import logging.handlers

#########################################################
#
"""
Functions to set up logs for DICOM server plugin
"""
#
#########################################################

def getHandler(root_log_dir):
    """
    Get a rotation file handler
    """
    log_file = os.path.join(root_log_dir, 'logs', 'dicom_server.log')
    # max file size is 5 MB, keep 5 backup logs
    rfh = logging.handlers.RotatingFileHandler(
              log_file, 'a', maxBytes=1024*1024*5, backupCount=5)
    fmt = logging.Formatter('%(asctime)s %(name)-12s %(levelname)-8s %(message)s',
                            datefmt='%y-%m-%d %H:%M:%S')
    rfh.setFormatter(fmt)
    return rfh

class StreamToLogger(object):
    """
    Fake file-like stream object that redirects writes to a logger instance.
    """
    def __init__(self, logger, log_level=logging.INFO):
        self.logger = logger
        self.log_level = log_level
        self.linebuf = ''

    def write(self, buf):
        for line in buf.rstrip().splitlines():
            self.logger.log(self.log_level, line.rstrip())
