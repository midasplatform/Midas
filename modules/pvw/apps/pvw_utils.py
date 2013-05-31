import sys
import os


def mhdToMha(file_name, raw_format='.zraw'):
    """Convert a mhd (+ raw file) to a mha file"""
    if os.path.isfile(file_name) and file_name.endswith('.mhd'):
        mha_name = file_name[:-4] + '.mha'
        raw_name = file_name[:-4] + raw_format
        with open(file_name, 'r') as header, open(mha_name, 'w') as mha_file,\
          open(raw_name, 'r') as raw_file:
            for line in header.readlines():
                if not line.startswith('ElementDataFile ='):
                    mha_file.write(line)
                elif line.startswith('ElementDataFile ='):
                    mha_file.write('ElementDataFile = LOCAL\n')
            r = raw_file.readlines()
            mha_file.writelines(r)

def mhaToMhd(file_name, raw_format='.raw'):
    """Convert a mha file to a mhd (+ raw) file"""
    if os.path.isfile(file_name) and file_name.endswith('.mha'):
        mhd_name = file_name[:-4] + '.mhd'
        raw_name = file_name[:-4] + raw_format
        with open(file_name, 'r') as mha_file, open(mhd_name, 'w') as mhd_file,\
          open(raw_name, 'w') as raw_file:
            end_header = 0
            for i, line in enumerate(mha_file.readlines()):
                if line.startswith('ElementDataFile ='):
                    end_header = i
                    break
                if i > 40:
                    break
            mha_file.seek(0)
            for k, line in enumerate(mha_file.readlines()):
                if k < end_header:
                    mhd_file.write(line)
                elif k == end_header:
                    mhd_file.write('ElementDataFile = '+raw_name+'\n')
                elif k > end_header:
                    raw_file.write(line)

def uploadItem(pydas_params, item_name, output_folder_id, src_dir,
               out_file=None, item_description=None):
    """Read everything in the src_dir and upload it to the Midas server as a 
    single item if out_file is not set, Otherwise only upload the out_file."""
    try:
        import pydas
    except ImportError:
        print 'Error: Pydas is not installed'
        raise Exception('Error: Pydas is not installed')
    # Create a new item, a folder id is required
    (email, apiKey, url) = pydas_params
    pydas.login(email=email, api_key=apiKey, url=url)
    if item_description is not None:
        item = pydas.session.communicator.create_item(pydas.session.token,
          item_name, output_folder_id, description=item_description)
    else:
        item = pydas.session.communicator.create_item(pydas.session.token,
          item_name, output_folder_id)
    item_id = item['item_id']
    if out_file is not None:
        # Only upload this one file
        uploadToken = pydas.session.communicator.generate_upload_token(
          pydas.session.token, item_id, out_file)
        filepath = os.path.join(src_dir, out_file)
        pydas.session.communicator.perform_upload(uploadToken, out_file,
          itemid=item_id, filepath=filepath)
    else:
        for filename in os.listdir(src_dir):
            uploadToken = pydas.session.communicator.generate_upload_token(
              pydas.session.token, item_id, filename)
            filepath = os.path.join(src_dir, filename)
            pydas.session.communicator.perform_upload(uploadToken, filename,
              itemid=item_id, filepath=filepath)
    return item_id
