import json
import os
import httplib
import urllib
from urlparse import urlparse
from exceptions import PydasException
import zipfile

class Communicator(object):
    """
    Class for exchanging data with a midas server.
    """

    def __init__(self, url=""):
        """
        Constructor
        """
        self.apiSuffix = '/api/json?method='
        self.serverUrl = url
        
    def encodeFileRequest (self, file_path, fields=[]):
        BOUNDARY = '----------bundary------'
        CRLF = '\r\n'
        body = []
        # Add the metadata about the upload first
        for key in fields.keys():
            value = fields[key]
            body.extend(
              ['--' + BOUNDARY,
               'Content-Disposition: form-data; name="%s"' % key,
               '',
               value,
               ])
        # Now add the file itself
        file_name = os.path.basename(file_path)
        f = open(file_path, 'rb')
        file_content = f.read()
        f.close()
        body.extend(
          ['--' + BOUNDARY,
           'Content-Disposition: form-data; name="file"; filename="%s"'
           % file_name,
           # The upload server determines the mime-type, no need to set it.
           'Content-Type: application/octet-stream',
           '',
           file_content,
           ])
        # Finalize the form body
        body.extend(['--' + BOUNDARY + '--', ''])
        return 'multipart/form-data; boundary=%s' % BOUNDARY, CRLF.join(body)

    def makeRequest(self, method, parameters=None, file=None):
        """
        Do the generic processing of a request to the server
        """
        url = self.serverUrl + self.apiSuffix + method
        request = None
        headers = {"Content-type": "application/x-www-form-urlencoded", "Accept": "text/plain"}
        
        if parameters:
            data = urllib.urlencode(parameters)
  
        else:
            parameters = dict()
            data = urllib.urlencode({})
            
        if file and os.path.exists(file):
            content_type, data = self.encodeFileRequest(file, parameters)
            headers = { 'Content-Type': content_type }
           
        h = httplib.HTTPConnection(urlparse(url)[1])      
        h.request('POST', urlparse(url)[2]+'?'+urlparse(url)[4], data, headers)            
        respnseRequest = h.getresponse()
        code = respnseRequest.status
        message = respnseRequest.reason
        content = respnseRequest.read() 
        
        if code != 200:
            raise PydasException("Request failed with HTTP error code "
                                 "%d" % code)
                                 
        try: response = json.loads(content)
        except Exception, e:
          print e
          print content
          return False

        if response['stat'] != 'ok':
            raise PydasException("Request failed with Midas error code "
                                 "%s: %s" % (response['code'],
                                             response['message']))
        return response['data']
        
        
    def getServerVersion(self):
        """
        Get the version from the server
        """
        response = self.makeRequest('midas.version')
        return response['version']

    def getServerInfo(self):
        """
        Get info from the server (this is an alias to getVersion on most
        platforms, but it returns the whole dictionary).
        """
        response = self.makeRequest('midas.info')
        return response
        
    def getItem(self, itemId, token = None):
        """
        Get item Info
        """
        parameters = dict()
        if token:        
          parameters['token'] = token
        parameters['id'] = itemId
        
        response = self.makeRequest('midas.item.get', parameters)
        return response

    def downloadItem(self, itemId, destination, token = None):
        """
        Download an item
        """
        parameters = dict()
        if token:        
          parameters['token'] = token
        parameters['id'] = itemId
                          
        itemInfo = self.getItem(itemId, token)
        revision = False
        for value in itemInfo['revisions']:
          revision = value
          
        for value in revision['bitstreams']:
          self.downloadBitstream(value['bitstream_id'], destination, token)
 
        return
        
    def getBitstream(self, bitstreamId, token = None):
        """
        Get bitstream Info
        """
        parameters = dict()
        if token:        
          parameters['token'] = token
        parameters['id'] = bitstreamId
        
        response = self.makeRequest('midas.bitstream.get', parameters)
        return response
        
    def downloadBitstream(self, bitstreamId, destination, token = None):
        """
        Download a bitstream
        """
        parameters = dict()
        if token:        
          parameters['token'] = token
        parameters['id'] = bitstreamId
        
        data = urllib.urlencode(parameters)    
        headers = {"Content-type": "application/x-www-form-urlencoded", "Accept": "text/plain"}        
        url = self.serverUrl + self.apiSuffix + 'midas.bitstream.download'        
        h = httplib.HTTPConnection(urlparse(url)[1])      
        h.request('POST', urlparse(url)[2]+'?'+urlparse(url)[4], data, headers)            
        respnseRequest = h.getresponse()
        code = respnseRequest.status

        message = respnseRequest.reason
        data = respnseRequest.read() 
        
        if os.path.isdir(destination) == False:
          destination = os.path.dirname(path)
        
        bitstreamInfo = self.getBitstream(bitstreamId, token)
        destination = destination+'/'+bitstreamInfo['name']

        with open(destination, 'wb') as f:
            f.write(data)
   
        return
        
    def getDefaultApiKey(self, email, password):
        """
        Gets the default api key given an email and password
        """
        parameters = dict()
        parameters['email'] = email
        parameters['password'] = password
        response = self.makeRequest('midas.user.apikey.default', parameters)
        return response['apikey']

    def loginWithApiKey(self, email, apikey, application='Default'):
        """
        Login and get a token using an email and apikey. If you do not specify
        a specific application, 'default' will be used
        """
        parameters = dict()
        parameters['email'] = email
        parameters['apikey'] = apikey
        parameters['appname'] = application
        response = self.makeRequest('midas.login', parameters)
        return response['token']

    def listUserFolders(self, token):
        """
        Use a users token to list the curent folders.
        """
        parameters = dict()
        parameters['token'] = token
        response = self.makeRequest('midas.user.folders', parameters)
        return response
    
    def generateUploadToken(self, token, itemid, filename, checksum=None):
        """
        Generate a token to use for upload.
        """
        parameters = dict()
        parameters['token'] = token
        parameters['itemid'] = itemid
        parameters['filename'] = filename
        if not checksum == None:
            parameters['checksum'] = checksum
        response = self.makeRequest('midas.upload.generatetoken', parameters)
        return response

    def createItem(self, token, name, parentid, description=None, uuid=None,
                   privacy='Public'):
        """
        BROKEN HACK TODO FIXME
        """
        parameters = dict()
        parameters['token'] = token
        parameters['name'] = name
        parameters['parentid'] = parentid
        parameters['privacy'] = privacy
        if not description == None:
            parameters['description'] = description
        if not uuid == None:
            parameters['uuid'] = uuid
        response = self.makeRequest('midas.item.create', parameters)
        return response

    def performUpload(self, uploadtoken, filename, length, mode=None,
                      folderid=None, itemid=None, revision=None):
        """
        BROKEN HACK TODO FIXME
        """
        parameters = dict()
        parameters['uploadtoken'] = uploadtoken
        parameters['filename'] = filename
        parameters['length'] = length
        if not mode == None:
            parameters['mode'] = mode
        if not folderid == None:
            parameters['folderid'] = folderid
        if not itemid == None:
            parameters['itemid'] = itemid
        if not revision == None:
            parameters['revision'] = revision
        response = self.makeRequest('midas.upload.perform', parameters)
        return response
