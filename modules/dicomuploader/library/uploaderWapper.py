import os
from DICOMHandler import DICOMListener


if __name__ == '__main__':
    # read the config file for DICOM uploader
    config_file = open('uploader.cfg')
    config = {}
    for line in config_file:
        line = line.strip()
        if line is not None and line != '':
            cols = line.split('=')
            config[cols[0]] = cols[1]
    config_file.close()

    if 'storescp_dir' not in config or not os.path.isdir(config['storescp_dir']):
        print "You must specify storescp's output directory (storescp_dir)  in uploader.cfg"
        exit(1)
    if 'storescp_exe' not in config:
        config['storescp_exe'] = 'scorescp'
    if 'storescp_port' not in config:
        config['storescp_port'] = 55555
    if 'storescp_study_timeout' not in config:
        config['storescp_study_timeout'] = 30

    if 'midas_destination' not in config:
        config['midas_destination'] = 'Public'

    # TOCHANGE: call uploader.py with storescp_dir as the callback_cmd
    callback_cmd = "'python uploader.py --email=%s --url=%s --apikey=%s --destination=%s --incomingdir=%s'" % ( \
      config['midas_email'], config['midas_url'], config['midas_api_key'], config['midas_destination'], config['storescp_dir'])


    # Start storescup listener
    myListener = DICOMListener(config['storescp_dir'], \
      callback_cmd, config['storescp_exe'],\
      config['storescp_port'], config['storescp_study_timeout'])
    myListener.start();
