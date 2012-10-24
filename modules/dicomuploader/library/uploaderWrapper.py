import os
import sys
import getopt
import signal
import logging

from DICOMHandler import DICOMListener

#########################################################
#
"""
Wrapper script called by DICOM uploader plugin
"""
#
#########################################################

class Usage(Exception):
    def __init__(self, msg):
        self.msg = msg


def killUploader(application, cmd):
    """
    Kill uploader associated processes
    """
    for line in os.popen("ps ax | grep %s" % cmd):
        fields = line.split()
        pid = fields[0]
        process = fields[4]
        shell_called_process = ''
        if len(fields) > 6:
            shell_called_process = fields[6]
        if process.find('grep') == 0:
            continue
        elif process.find('python') == 0 or process.find('storescp') == 0 or shell_called_process.find('storescp') == 0:
            #Kill the Process. Change signal.SIGHUP to signal.SIGKILL if you like
            os.kill(int(pid), signal.SIGHUP)

def main():
    """
    Wrapper function to start and stop storescp listener
    """
    try:
        opts, args = getopt.getopt(sys.argv[1:], "hros:p:t:k:c:i:u:e:a:d:", ["help", "start", "stop", \
            "storescp=", "port=", "timeout=", "scriptpath=", "dcm2xml=", "incoming=", \
            "url=", "email=", "apikey=", "dest="])
    except getopt.error, msg:
        raise Usage(msg)
    start = False
    url = ''
    user_email = ''
    apikey = ''
    dest_folder = ''
    incoming_dir = ''
    storescp_cmd = ''
    script_path = ''
    dcm2xml_cmd = ''
    storescp_port = ''
    storescp_timeout = ''
    for opt, arg in opts:
        if opt in ('-h', "--help"):
            sample = 'uploader.py --start -s <storescp_cmd> ' \
              '-p <storescp_port> -t <storescp_studay_timeout> ' \
              '-k <script_path> -c <dcm2xml_cmd> -i <incoming_dir> ' \
              '-u <midas_url> -e <midas_user_email> ' \
              '-a <midas_api_key> -d <midas_destination_folder>'
            print sample
            sys.exit()
        elif opt in ("-r", "--start"):
            start = True
        elif opt in ("-o", "--stop"):
            start = False
        elif opt in ("-s", "--storescp"):
            storescp_cmd = arg
        elif opt in ("-p", "--port"):
            storescp_port = arg
        elif opt in ("-t", "--timeout"):
            storescp_timeout = arg
        elif opt in ("-k", "--scriptpath"):
            script_path = arg
        elif opt in ("-c", "--dcm2xml"):
            dcm2xml_cmd = arg
        elif opt in ("-i", "--incoming"):
            incoming_dir = arg
        elif opt in ("-u", "--url"):
            url = arg
        elif opt in ("-e", "--email"):
            user_email = arg
        elif opt in ("-a", "--apikey"):
            apikey = arg
        elif opt in ("-d", "--dest"):
            dest_folder = arg
    # start/stop storescup listener
    myListener = DICOMListener()
    if start:
        # callback command used by storescp '--eostudy-timeout' option
        callback_cmd = "'python %s -c %s -i %s -u %s -e %s -a %s -d %s'" % ( \
          script_path, dcm2xml_cmd, incoming_dir, url, user_email, apikey, dest_folder)
        retcode = myListener.start(incoming_dir, callback_cmd, \
          storescp_cmd, storescp_port, storescp_timeout)
        return retcode
    else:
        if not storescp_cmd:
            storescp_cmd = 'storescp'
        app_name = 'uploaderWapper'
        retcode = killUploader(app_name, storescp_cmd)
        return retcode

if __name__ == "__main__":
    sys.exit(main())

