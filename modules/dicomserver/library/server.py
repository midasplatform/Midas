#!/usr/bin/python

import os
import sys
import subprocess
import re
import shutil
import getopt
import pydas
import logging

from logsetting import getHandler, StreamToLogger

# create logger
logger = logging.getLogger('server')
logger.setLevel(logging.INFO)
stdout_logger = logging.getLogger('STDOUT')
stdout_logger.setLevel(logging.INFO)
stderr_logger = logging.getLogger('STDERR')
stderr_logger.setLevel(logging.INFO)

#########################################################
#
"""
Functions to process DICOM files and upload them to Midas
"""
#
#########################################################

def groupFilesbySeriesUID(dcm2xmlCmd, rootDir):
    """
    group DICOM files with SeriesInstanceUID and move them to the processing directory
    """
    logger.info("Process DICOM files and group them (be moved to the same directory) by their SeriesInstanceUID")
    processing_dir = os.path.join(rootDir, 'processing')
    if not os.path.isdir(processing_dir):
        os.mkdir(processing_dir, 0777)
    study_dirs = os.listdir(rootDir)
    study_dirs.remove('processing')
    study_dirs.remove('logs')
    study_dirs.remove('pacs')
    received_files_counter = 0
    processed_files_counter = 0
    for study_dir in study_dirs:
        logger.info("Processing %s:" % study_dir)
        dicom_files = os.listdir(os.path.join(rootDir, study_dir))
        for dicom_file in dicom_files:
            received_files_counter += 1
            dicom_file_abspath = os.path.join(rootDir, study_dir, dicom_file)
            proc = subprocess.Popen([dcm2xmlCmd, dicom_file_abspath], stdout=subprocess.PIPE)
            xml_output = proc.communicate()[0].splitlines()
            for line in xml_output:
                uid_search = re.search('<.*SeriesInstanceUID.*>(.*)</.*>', line)
                if (uid_search is not None):
                    series_instance_uid = uid_search.groups()[0]
                    series_dir = os.path.join(processing_dir, series_instance_uid)
                    if not os.path.isdir(series_dir):
                        os.mkdir(series_dir, 0777)
                    # move files
                    os.rename(dicom_file_abspath, os.path.join(series_dir, dicom_file))
                    processed_files_counter += 1
                    break
        logger.info("  Summary: received %i files, processed %i files"% (received_files_counter, processed_files_counter))
        os.rmdir(os.path.join(rootDir, study_dir))
    return True

def uploadToMidas(processingDir, midasEmail, midasApiKey, midasUrl, midasDestination):
    """
    rename DICOM files and put files with same SeriesInstanceUID into the same directory;
    upload files to Midas using Pydas, one item per directory.
    """
    pydas.login(email=midasEmail, api_key=midasApiKey, url=midasUrl)
    extract_dicom_callback = lambda communicator, token, item_id: communicator.extract_dicommetadata(token, item_id)
    pydas.add_item_upload_callback(extract_dicom_callback)

    series_dirs = os.listdir(processingDir)
    for series_dir in series_dirs:
        series_dir_abspath = os.path.join(processingDir, series_dir)
        logger.info("use Pydas to upload DOCOM files who have SeriesInstanceUID : %s" % series_dir)
        pydas.upload(series_dir_abspath, destination=midasDestination, leaf_folders_as_items=True)
        shutil.rmtree(series_dir_abspath)
    return True

class Usage(Exception):
    def __init__(self, msg):
        self.msg = msg


def main():
    try:
        opts, args = getopt.getopt(sys.argv[1:], "hc:i:u:e:a:d:", ["help", "dcm2xml=", "incoming=", "url=", "email=", "apikey=", "dest=", ])
    except getopt.error, msg:
        raise Usage(msg)
    url = ''
    user_email = ''
    apikey = ''
    dest_folder = ''
    incoming_dir = ''
    for opt, arg in opts:
        if opt in ('-h', "--help"):
            print 'server.py -c <dcm2xml_cmd> -i <incoming_dir> -u <midas_url> -e <midas_user_email>  -a <midas_api_key> -d <midas_destination_folder>'
            sys.exit()
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

    # set up normal logger
    logger.addHandler(getHandler(incoming_dir.strip()))

    # log stdout and stderr during the period that received DICOM files are
    # processed in local disk and uploaded to Midas using Pydas
    stdout_logger.addHandler(getHandler(incoming_dir.strip()))
    out_log = StreamToLogger(stdout_logger, logging.INFO)
    sys.stdout = out_log
    stderr_logger.addHandler(getHandler(incoming_dir.strip()))
    err_log = StreamToLogger(stderr_logger, logging.ERROR)
    sys.stderr = err_log

    # move files to processing directory
    groupFilesbySeriesUID(dcm2xml_cmd, incoming_dir)

    # group files by SeriesInstanceUID
    processing_dir = os.path.join(incoming_dir, 'processing')
    # upload files to midas
    uploadToMidas(processing_dir, user_email, apikey, url, dest_folder)

if __name__ == "__main__":
    sys.exit(main())

