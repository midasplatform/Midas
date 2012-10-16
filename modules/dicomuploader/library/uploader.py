import os
import sys
import subprocess
import re
import shutil
import getopt

import pydas


def groupFilesbySeriesUID(rootDir):
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
            p1 = subprocess.Popen(["dcm2xml", dicom_file_abspath], stdout=subprocess.PIPE)
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
        opts, args = getopt.getopt(sys.argv[1:], "heuadi", ["help", "email=", "url=", "apikey=", "destination=", "incomingdir="])
    except getopt.error, msg:
        raise Usage(msg)
    url = ''
    email = ''
    apikey = ''
    dest = 'Private'
    incoming_dir = ''
    for opt, arg in opts:
        if opt in ('-h', "--help"):
            print 'uploader.py --email=<midas_email> --url=<midas_url> --apikey=<midas_api_key> --destination=<midas_destination>, --incomingdir=<incoming_dir>'
            sys.exit()
        elif opt in ("-e", "--email"):
            email = arg
        elif opt in ("-u", "--url"):
            url = arg
        elif opt in ("-a", "--apikey"):
            apikey = arg
        elif opt in ("-d", "--destination"):
            dest = arg
        elif opt in ("-i", "--incomingdir"):
            incoming_dir = arg

    # move files to processing dir
    groupFilesbySeriesUID(incoming_dir)

    # upload files to midas
    processingDir = os.path.join(incoming_dir, 'processing')
    uploadToMidas(processingDir, email, apikey, url, dest)


if __name__ == "__main__":
    main()

