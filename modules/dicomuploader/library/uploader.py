#!/usr/bin/python

import os
import sys
import subprocess
import re
import shutil
import getopt
import pydas


def groupFilesbySeriesUID(dcm2xmlCmd, rootDir):
    """
    group DICOM files with SeriesInstanceUID and move them to the processing directory
    """
    print "start processing files"
    processing_dir = os.path.join(rootDir, 'processing')
    if not os.path.isdir(processing_dir):
        os.mkdir(processing_dir, 0777)
    study_dirs = os.listdir(rootDir)
    study_dirs.remove('processing')
    for study_dir in study_dirs:
        dicom_files = os.listdir(os.path.join(rootDir, study_dir))
        for dicom_file in dicom_files:
            # TODO: use minidom or re to replace grep for cross-platform use
            dicom_file_abspath = os.path.join(rootDir, study_dir, dicom_file)
            p1 = subprocess.Popen([dcm2xmlCmd, dicom_file_abspath], stdout=subprocess.PIPE)
            p2 = subprocess.Popen(["grep", "SeriesInstanceUID"], stdin=p1.stdout, stdout=subprocess.PIPE)
            series_instance_uid_element = p2.communicate()[0]
            uid_search = re.search('<.*>(.*)</.*>', series_instance_uid_element)
            series_instance_uid = uid_search.groups()[0]
            series_dir = os.path.join(processing_dir, series_instance_uid)
            if not os.path.isdir(series_dir):
                os.mkdir(series_dir, 0777)
            # move files
            os.rename(dicom_file_abspath, os.path.join(series_dir, dicom_file))
        os.rmdir(os.path.join(rootDir, study_dir))

    return True

def uploadToMidas(processingDir, midasEmail, midasApiKey, midasUrl, midasDestination):
    """
    rename dicom files and put files with same SeriesInstanceUID into the same directory
    """
    pydas.login(email=midasEmail, api_key=midasApiKey, url=midasUrl)
    extract_dicom_callback = lambda communicator, token, item_id: communicator.extract_dicommetadata(token, item_id)
    pydas.add_item_upload_callback(extract_dicom_callback)

    series_dirs = os.listdir(processingDir)
    print "start uploading files to Midas"
    for series_dir in series_dirs:
        series_dir_abspath = os.path.join(processingDir, series_dir)
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
            print 'uploader.py -c <dcm2xml_cmd> -i <incoming_dir> -u <midas_url> -e <midas_user_email>  -a <midas_api_key> -d <midas_destination_folder>'
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

    # move files to processing directory
    groupFilesbySeriesUID(dcm2xml_cmd, incoming_dir)

    # upload files to midas
    processing_dir = os.path.join(incoming_dir, 'processing')
    uploadToMidas(processing_dir, user_email, apikey, url, dest_folder)


if __name__ == "__main__":
    main()

